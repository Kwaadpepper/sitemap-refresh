<?php

namespace Kwaadpepper\SitemapRefresh\Lib;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Kwaadpepper\SitemapRefresh\Exceptions\SitemapResolveUrlException;
use Spatie\Sitemap\Sitemap as SpatieSitemap;
use Spatie\Sitemap\Tags\Url;
use Symfony\Component\Mime\MimeTypes;

class Sitemap
{
    /** @var \Spatie\Sitemap\Sitemap */
    private $sitemap;

    /** @var \Illuminate\Support\Collection<\Kwaadpepper\SitemapRefresh\Lib\Tag> */
    private $tagList;

    /** @var array<string, bool> */
    private $normalizedUrls;

    /**
     * Sitemap
     *
     * @param \Spatie\Sitemap\Sitemap $sitemap
     * @return void
     */
    public function __construct(\Spatie\Sitemap\Sitemap $sitemap)
    {
        // *Assign new sitemap
        $this->sitemap = SpatieSitemap::create();
        $this->tagList = collect();
        $this->normalizedUrls = [];
        \collect($sitemap->getTags())->map(function (Url $spatie_tag) {
            $this->addTag($spatie_tag);
        });
        $this->assignPrioritiesAndFrequencies();
    }

    /**
     * Appends and merge urls list for a specific route name
     *
     * ! Use this to complete the sitemap
     *
     * @param iterable $urlsList A list of string with the urls to merge in.
     * @return void
     */
    public function mergeUrls(iterable $urlsList)
    {
        \collect($urlsList)->each(function (string $url) {
            $spatie_tag = new Url($url);
            $this->addTag($spatie_tag);
        });

        $this->assignPrioritiesAndFrequencies();
    }

    /**
     * Export sitemap to string
     *
     * @return string
     */
    public function output(): string
    {
        return $this->sitemap->render();
    }

    /**
     * Export sitemap tags
     *
     * @return \Illuminate\Support\Collection<\Kwaadpepper\SitemapRefresh\Lib\Tag>
     */
    public function getTagList(): Collection
    {
        return $this->tagList;
    }

    /**
     * Export sitemap to path
     *
     * @param string $path
     * @return void
     */
    public function export(string $path)
    {
        $this->sitemap->writeToFile($path);
    }

    /**
     * Add spatie_tag to list
     *
     * @param  \Spatie\Sitemap\Tags\Url $spatie_tag
     *
     * @return void
     */
    private function addTag(Url $spatie_tag): void
    {
        $normalizedUrl = UrlGeneratorContext::normalize($spatie_tag->url);

        try {
            /** @var string|null $query */
            $query = \parse_url($normalizedUrl, \PHP_URL_QUERY);

            if ($query !== null && $query !== '') {
                return;
            }

            if (isset($this->normalizedUrls[$normalizedUrl])) {
                return;
            }

            $spatie_tag->setUrl($normalizedUrl);
            $tag = new Tag($spatie_tag);

            $this->normalizedUrls[$normalizedUrl] = true;
            $this->tagList->push($tag);
            $this->sitemap->add($spatie_tag);
        } catch (SitemapResolveUrlException $e) {
            $headMimeType = Utils::getUrlMimeType($normalizedUrl);
            if (in_array('html', MimeTypes::getDefault()->getExtensions($headMimeType))) {
                // * Report uniquement si l'url pointe sur une page HMTL.
                Log::warning('SitemapRefresh: Could not resolve url for sitemap entry: ' . $normalizedUrl . ' - ' . $e->getMessage());
            }
        }
    }

    /**
     * Assign all tag priorities
     *
     * @return void
     */
    private function assignPrioritiesAndFrequencies()
    {
        $defaultFrequency = config('sitemap-refresh.routeDefaultFrequency', Url::CHANGE_FREQUENCY_DAILY);
        $defaultPriority  = config('sitemap-refresh.routeDefaultPriority', 0.5);
        $frequencyList    = \collect(config('sitemap-refresh.routeFrequencies', []));
        $priorityList     = \collect(config('sitemap-refresh.routePriorities', []));

        $this->tagList->each(function (Tag $tag) use (
            $defaultFrequency,
            $defaultPriority,
            $frequencyList,
            $priorityList
        ) {
            $tag->setChangeFrequency($defaultFrequency);
            $tag->setPriority($defaultPriority);

            // * Assign frequency
            /** @var string|null $routeFrequencyPattern */
            $routeFrequencyPattern = $frequencyList->keys()->first(function (string $namePattern) use ($tag) {
                return Utils::routeIsInList($tag->getRoute(), [$namePattern]);
            });
            if ($routeFrequencyPattern !== null) {
                /** @var string $routeFrequency */
                $routeFrequency = $frequencyList->get($routeFrequencyPattern);
                $tag->setChangeFrequency($routeFrequency);
            }

            // * Assign priority
            /** @var string|null $routePriorityPattern */
            $routePriorityPattern = $priorityList->keys()->first(function (string $namePattern) use ($tag) {
                return Utils::routeIsInList($tag->getRoute(), [$namePattern]);
            });
            if ($routePriorityPattern !== null) {
                /** @var float $routePriority */
                $routePriority = $priorityList->get($routePriorityPattern);
                $tag->setPriority($routePriority);
            }
        });
    }
}
