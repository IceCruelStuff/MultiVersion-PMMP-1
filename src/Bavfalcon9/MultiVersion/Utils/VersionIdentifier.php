<?php

namespace Bavfalcon9\MultiVersion\Utils;

class VersionIdentifier {
    private $version = '0.0.0';
    private $higher = ['x', '^', '*'];

    public function __construct(String $v='1.12.0') {
        $this->version = $v;
    }

    public function getSupported(Array $versions): Array {
        $finished = [];

        $required = [
            "update" => explode('.', $this->version)[0] ?? '1',
            "release" => explode('.', $this->version)[1] ?? '0',
            "build" => explode('.', $this->version)[2] ?? 'x'
        ];

        foreach ($versions as $version=>$v) {
            $split = explode('.', $version);
            $update  = $split[0] ?? '1';
            $release = $split[1] ?? '0';
            $build   = $split[2] ?? '0';

            if ($required['update'] !== $update) continue;
            if ($required['release'] !== $release) continue;

            if (in_array($required['build'], $this->higher)) {
                array_push($finished, $version);
                continue;
            }

            if ($required['build'] !== $build) continue;

            array_push($finished, $version);
        }

        return $finished;
    } 
}