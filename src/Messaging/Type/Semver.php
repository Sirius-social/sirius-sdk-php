<?php


namespace Siruis\Messaging\Type;


use PHLAK\SemVer\Exceptions\InvalidVersionException;
use PHLAK\SemVer\Version;

class Semver extends Version
{
    CONST SEMVER_RE = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)(?:\.(0|[1-9]\d*))?$/';

    /**
     * @param string $version_str
     * @return \PHLAK\SemVer\Version
     * @throws InvalidVersionException
     */
    public static function fromString(string $version_str): Version
    {
//        preg_match(self::SEMVER_RE, $version_str, $matches);
//        if ($matches) {
//
//        }

        return self::parse($version_str);
    }

    public function getMajor(): int
    {
        return $this->major;
    }

    public function getMinor(): int
    {
        return $this->minor;
    }

    public function getPatch()
    {
        return $this->patch;
    }

    public function getPreRelease(): ?string
    {
        return $this->preRelease;
    }

    public function getBuild(): ?string
    {
        return $this->build;
    }
}