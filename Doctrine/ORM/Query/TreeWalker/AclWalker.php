<?php
namespace Neutron\ComponentBundle\Doctrine\ORM\Query\TreeWalker;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\ORM\Query\Exec\SingleSelectExecutor;

use Doctrine\ORM\Query\AST\SelectStatement;

use Doctrine\ORM\Query\SqlWalker;

class AclWalker extends SqlWalker
{
    const HINT_ACL_OPTIONS = '__neutron.acl.options';
    
    protected $conn;
    
    protected $platform;
    
    protected $attributes;
    
    protected $options;
    
    
    public function __construct($query, $parserResult, array $queryComponents)
    {
        parent::__construct($query, $parserResult, $queryComponents); 
        $this->conn = $this->getConnection();
        $this->platform = $this->getConnection()->getDatabasePlatform();
        $this->extractAttributes($queryComponents);
        $this->validateOptions($this->getQuery()->getHint(self::HINT_ACL_OPTIONS));
    }
    
    /**
     * (non-PHPdoc)
     * @see Doctrine\ORM\Query.SqlWalker::getExecutor()
     */
    public function getExecutor($AST)
    {
        if (!$AST instanceof SelectStatement) {
            throw new \UnexpectedValueException('Translation walker should be used only on select statement');
        }

        return new SingleSelectExecutor($AST, $this);
    }
    
    /**
     * (non-PHPdoc)
     * @see Doctrine\ORM\Query.SqlWalker::walkWhereClause()
     */
    public function walkWhereClause($whereClause)
    {
        $result = parent::walkWhereClause($whereClause);
        return $this->appendAcl($result);
    }
    
    /**
     * 
     *
     * @param array $queryComponents
     * @return void
     */
    private function extractAttributes(array $queryComponents)
    {
      
        foreach ($queryComponents as $alias => $comp) {
            if ($comp['parent'] === null && $comp['nestingLevel'] == 0 && isset($comp['metadata'])) {
                $this->attributes = array();
                $meta = $comp['metadata'];
                
                if (count($meta->getIdentifierFieldNames()) > 1){
                    throw new \LogicException('AclWalker does not support compositive keys.');
                }
                $this->attributes['tableAlias'] = $this->getSQLTableAlias($meta->getTableName(), $alias);
                $this->attributes['identifier'] = 
                    $meta->getQuotedColumnName($meta->getSingleIdentifierFieldName(), $this->platform);
                $this->attributes['class'] = $meta->getName();
                break;
            }  
        }
    }
    
    /**
     * 
     * @param string $whereClause
     */
    private function appendAcl($whereClause)
    {
        if (!$this->attributes){
            return $whereClause;
        }
        
        $subquery = $this->getEntitiesMatchingRoleMaskSql($this->attributes['class'], $this->options['roles'], $this->options['mask']);
        
        if ($whereClause == ''){
            $whereClause = " WHERE {$this->attributes['tableAlias']}.{$this->attributes['identifier']} IN ({$subquery})";
        } else {
            $whereClause .= " AND {$this->attributes['tableAlias']}.{$this->attributes['identifier']} IN ({$subquery})";
        }
        
        return $whereClause;
    }
    
    /**
     * 
     * @param unknown_type $class
     * @param array $roles
     * @param unknown_type $mask
     */
    private function getEntitiesMatchingRoleMaskSql($class, array $roles, $mask)
    {
        $mask = (int) $mask;
        
        $qb = $this->conn->createQueryBuilder();
        
        $orX = $qb->expr()->orX();
        
        if (count($roles) > 0){
            foreach ($roles as $role){
                $orX->add($qb->expr()->eq('s.identifier', $this->conn->quote($role)));
            } 
        } else {
            $orX->add($qb->expr()->eq('s.identifier', $this->conn->quote('NO_EXISTING_ROLE')));
        }
        
        
        $qb
            ->select('oid.object_identifier')
            ->from('acl_entries', 'e')
            ->innerJoin('e', 'acl_object_identities', 'oid', 'oid.id = e.object_identity_id')
            ->innerJoin('e', 'acl_security_identities', 's', 's.id = e.security_identity_id')
            ->innerJoin('e', 'acl_classes', 'class', 'class.id = e.class_id')
            ->andWhere("e.mask & {$mask}")
            ->andWhere($orX)
            ->andWhere($qb->expr()->eq('class.class_type', $this->conn->quote($class)))
            ->groupBy('oid.object_identifier')
        ;
        
        return $qb->getSql();
        
    }
    
    /**
     * 
     * @param unknown_type $hint
     * @throws \RuntimeException
     */
    private function validateOptions($hint)
    {
        
        if (!is_array($hint)){
            throw new \RuntimeException('Hint: "AclWalker::HINT_ACL_OPTIONS" is not set!');
        }
        
        $resolver = new OptionsResolver();
        
        $resolver->setRequired(array('roles', 'mask'));
        
        $resolver->setAllowedTypes(array(
            'roles' => array('array'),    
            'mask'  => array('integer'),      
        ));
        
        $options = $resolver->resolve($hint);
        $this->options = $options; 
    }
}