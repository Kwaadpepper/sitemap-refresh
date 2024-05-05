<?php

namespace Kwaadpepper\SitemapRefresh\Lib;

use Illuminate\Support\Collection;
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
        try {
            $tag = new Tag($spatie_tag);
            /** @var array */
            $queries = \parse_url($tag->getUrl(), \PHP_URL_QUERY);
            // ! Ignore tags with urls that have Queries.
            if (\collect($queries)->filter()->count()) {
                return;
            }
            $this->tagList->push($tag);
            $this->sitemap->add($spatie_tag);
        } catch (SitemapResolveUrlException $e) {
            $headMimeType = Utils::getUrlMimeType($spatie_tag->url);
            if (in_array('html', MimeTypes::getDefault()->getExtensions($headMimeType))) {
                // * Report uniquement si l'url pointe sur une page HMTL.
                report($e);
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
            /** @var string|null $routeFrequency */
            // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
            $routeFrequency = $frequencyList->first(function ($frequency, $namePattern) use ($tag) {
                return Utils::routeIsInList($tag->getRoute(), [$namePattern]);
            });
            if ($routeFrequency !== null) {
                $tag->setChangeFrequency($routeFrequency);
            }

            // * Assign priority
            /** @var float|null $routePriority */
            // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
            $routePriority = $priorityList->first(function ($priority, $namePattern) use ($tag) {
                return Utils::routeIsInList($tag->getRoute(), [$namePattern]);
            });
            if ($routePriority !== null) {
                $tag->setPriority($routePriority);
            }
        });
    }
}
