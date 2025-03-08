<?php

namespace OneClick;

use DateTime;
use MapasCulturais\App;
use MapasCulturais\Entity;
use Doctrine\ORM\Mapping as ORM;
use MapasCulturais\Traits\EntityMetadata;


/**
 * Settings
 *
 * @property-read int $id
 * 
 * @property object $metadata 
 * @property DateTime $createTimestamp 
 * @property DateTime $updateTimestamp 
 * @property int $status 
 * 
 * 
 * @ORM\Table(name="settings")
 * @ORM\Entity
 * @ORM\entity(repositoryClass="OneClick\Repositories\Settings")
 */
class Settings extends Entity
{
    use EntityMetadata;

    const STATUS_ACTIVE = 1;
    const STATUS_DISABLED = 2;

    protected $__enableMagicGetterHook = true;
    protected $__enableMagicSetterHook = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="settings_id_seq", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint", nullable=false)
     */
    protected $status = self::STATUS_ACTIVE;


    /**
     * @var object
     *
     * @ORM\Column(name="metadata", type="json", nullable=false)
     */
    protected $metadata;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_timestamp", type="datetime", nullable=false)
     */
    protected $createTimestamp;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_timestamp", type="datetime", nullable=true)
     */
    protected $updateTimestamp;

    /**
     * @var integer
     *
     * @ORM\Column(name="subsite_id", type="integer", nullable=true)
     */
    protected $subsiteId;

    /**
     * @ORM\OneToMany(targetEntity="OneClick\SettingsMeta", mappedBy="owner", cascade={"remove"}, orphanRemoval=true)
     */
    protected $__metadata = [];

    /**
     * Returns the owner User of this entity
     *
     * @return \MapasCulturais\Entities\User
     */
    function getOwnerUser()
    {
        $app = App::i();
        return $app->user;;
    }



    /**
     * @return array 
     */
    public function fromToFilesMetadata(): array
    {
        return [
            'home-header' => 'bannerImageData',
            'home-opportunities' => 'entitiesOpportunityImageData',
            'home-events' => 'entitiesEventImageData',
            'home-spaces' => 'entitiesSpaceImageData',
            'home-agents' => 'entitiesAgentImageData',
            'home-projects' => 'entitiesProjectImageData',
            'home-register' => 'registerImageData',
            'logo-image' => 'imageLogoData',
            'favicon-svg' => 'faviconSvgData',
            'favicon-png' => 'faviconPngData',
            'share-image' => 'shareData',
            'mail-image' => 'mailImageData'
        ];
    }
}
