<?php
/**
 * AOM - Piwik Advanced Online Marketing Plugin
 *
 * @author Daniel Stonies <daniel.stonies@googlemail.com>
 * @author André Kolell <andre.kolell@gmail.com>
 */
namespace Piwik\Plugins\AOM\Platforms;

class MergerPlatformDataOfVisit
{
    /**
     * @var string
     */
    private $platformName;

    /**
     * @var array|null
     */
    private $platformData;

    /**
     * @var null
     */
    private $platformKey;

    /**
     * @var null
     */
    private $platformRowId;

    /**
     * @param string $platformName
     * @param array|null $platformData
     * @param null $platformKey
     * @param null $platformRowId
     */
    public function __construct($platformName, array $platformData = null, $platformKey = null, $platformRowId = null)
    {
        $this->platformName = $platformName;
        $this->platformData = $platformData;
        $this->platformKey = $platformKey;
        $this->platformRowId = $platformRowId;
    }

    /**
     * @return string
     */
    public function getPlatformName()
    {
        return $this->platformName;
    }

    /**
     * @return null
     */
    public function getPlatformRowId()
    {
        return $this->platformRowId;
    }

    /**
     * @param null $platformRowId
     * @return $this
     */
    public function setPlatformRowId($platformRowId)
    {
        $this->platformRowId = $platformRowId;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getPlatformData()
    {
        return $this->platformData;
    }

    /**
     * @param array|null $platformData
     * @return $this
     */
    public function setPlatformData($platformData)
    {
        $this->platformData = $platformData;

        return $this;
    }

    /**
     * @param array|null $platformData
     * @return $this
     */
    public function addPlatformData($platformData)
    {
        $this->platformData = array_merge($this->platformData, $platformData);

        return $this;
    }

    /**
     * @return null
     */
    public function getPlatformKey()
    {
        return $this->platformKey;
    }

    /**
     * @param null $platformKey
     * @return $this
     */
    public function setPlatformKey($platformKey)
    {
        $this->platformKey = $platformKey;

        return $this;
    }
}
