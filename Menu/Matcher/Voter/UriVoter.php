<?php
namespace Neutron\ComponentBundle\Menu\Matcher\Voter;

use Knp\Menu\ItemInterface;

use Knp\Menu\Matcher\Voter\VoterInterface;

class UriVoter implements VoterInterface
{
    private $uri;
    
    public function __construct($uri = null)
    {
        $this->uri = $uri;
    }
    
    public function setUri($uri)
    {
        $this->uri = $uri;
    }
    
    /**
     * Checks whether an item is current.
     *
     * If the voter is not able to determine a result,
     * it should return null to let other voters do the job.
     *
     * @param ItemInterface $item
     * @return boolean|null
     */
    public function matchItem(ItemInterface $item)
    {
        if (null === $this->uri || null === $item->getUri()) {
            return null;
        }
    
        if ($item->getUri() === $this->uri) {
            
            return true;
        }
    
        return null;
    }
}