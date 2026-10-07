<?php

declare(strict_types=1);

namespace App\StatamicImport;

use Symfony\Component\Yaml\Yaml;

/**
 * Reads Statamic flat files (.md entries with YAML frontmatter, .yaml configs).
 */
final class Statamic
{
    /**
     * Parse a Statamic .md file into [frontmatter, markdown body].
     *
     * @return array{0: array<string, mixed>, 1: string}
     */
    public static function parseDoc(string $path): array
    {
        $raw = (string) file_get_contents($path);
        if (preg_match('/\A---\R(.*?)\R---\R?(.*)\z/s', $raw, $m) === 1) {
            $data = Yaml::parse($m[1]);

            return [is_array($data) ? $data : [], trim($m[2])];
        }

        $data = Yaml::parse($raw);

        return [is_array($data) ? $data : [], ''];
    }

    /** @return array<string, mixed> */
    public static function readYaml(string $path): array
    {
        $data = Yaml::parseFile($path);

        return is_array($data) ? $data : [];
    }

    /**
     * Statamic dated collection filenames look like `2022-07-15-0751.slug.md`.
     *
     * @return array{0: string, 1: string|null} [slug, 'Y-m-d H:i:s'|null]
     */
    public static function slugFromFilename(string $filename): array
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        if (preg_match('/^(\d{4}-\d{2}-\d{2})-(\d{4})\.(.+)$/', $name, $m) === 1) {
            return [$m[3], $m[1].' '.substr($m[2], 0, 2).':'.substr($m[2], 2).':00'];
        }

        return [$name, null];
    }
}
