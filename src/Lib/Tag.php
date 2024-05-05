<?php

namespace Kwaadpepper\SitemapRefresh\Lib;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Kwaadpepper\SitemapRefresh\Exceptions\SitemapException;
use Spatie\Sitemap\Tags\Url;

class Tag
{
    /**
     * Accepted Freqencies that can be set
     *
     * @var string[]
     */
    private $acceptedFreqencies = [
        Url::CHANGE_FREQUENCY_ALWAYS,
        Url::CHANGE_FREQUENCY_HOURLY,
        Url::CHANGE_FREQUENCY_DAILY,
        Url::CHANGE_FREQUENCY_WEEKLY,
        Url::CHANGE_FREQUENCY_MONTHLY,
        Url::CHANGE_FREQUENCY_YEARLY,
        Url::CHANGE_FREQUENCY_NEVER
    ];

    /**
     * Priorities that can be set
     *
     * @var float[]
     */
    private $acceptedPriorities = [1.0, 0.9, 0.8, 0.7, 0.6, 0.5, 0.4, 0.3, 0.2, 0.1, 0.0];

    /** @var \Spatie\Sitemap\Tags\Url */
    private $tag;

    /** @var \Illuminate\Routing\Route */
    private $route;

    /** @var \Illuminate\Database\Eloquent\Model|null */
    private $concernedModel;

    /**
     * Tag
     *
     * @param \Spatie\Sitemap\Tags\Url $tag
     * @return void
     * @throws \Kwaadpepper\SitemapRefresh\Exceptions\SitemapResolveUrlException If the method of the route is not
     *                                                                     GET or not found.
     */
    public function __construct(\Spatie\Sitemap\Tags\Url $tag)
    {
        $this->tag            = $tag;
        $this->route          = Utils::resolveUrl($tag->url);
        $this->concernedModel = Utils::fetchConcernedModel($this->route);

        if ($this->concernedModel) {
            $date = $this->concernedModel->updated_at ??
                $this->concernedModel->created_at ??
                \now();
            try {
                $this->tag->setLastModificationDate(Carbon::parse($date)->toDateTime());
            } catch (InvalidFormatException $e) {
                Log::warning("Date is on a invalid format {$date} for url {$tag->url}");
            }
        }
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->tag->url;
    }

    /**
     * Get change frequency
     *
     * @return string
     */
    public function getLastChange(): string
    {
        if (!isset($this->tag->lastModificationDate)) {
            return '';
        }
        return Carbon::parse($this->tag->lastModificationDate)->isoFormat('DD/MM/YYYY HH\hmm');
    }

    /**
     * Set change freqency
     *
     * @param string $freqency
     * @return void
     * @throws \Kwaadpepper\SitemapRefresh\Exceptions\SitemapException If freqency is invalid.
     */
    public function setChangeFrequency(string $freqency): void
    {

        if (!\in_array($freqency, $this->acceptedFreqencies, true)) {
            $aFreq = \implode(',', $this->acceptedFreqencies);
            throw new SitemapException("config error in routeFrequencies, {$freqency} not in {$aFreq}");
        }
        $this->tag->setChangeFrequency($freqency);
    }

    /**
     * Get change frequency
     *
     * @return string
     */
    public function getChangeFrequency(): string
    {
        return $this->tag->changeFrequency;
    }

    /**
     * Set priority
     *
     * @param float $priority
     * @return void
     * @throws \Kwaadpepper\SitemapRefresh\Exceptions\SitemapException If priority is invalid.
     */
    public function setPriority(float $priority): void
    {
        if (!\in_array($priority, $this->acceptedPriorities, true)) {
            $aPrio = \implode(',', $this->acceptedPriorities);
            throw new SitemapException("config error in routePriorities, {$priority} not in {$aPrio}");
        }
        $this->tag->setPriority($priority);
    }

    /**
     * Get priority
     *
     * @return float
     */
    public function getPriority(): float
    {
        return $this->tag->priority;
    }

    /**
     * Get route
     *
     * @return \Illuminate\Routing\Route
     */
    public function getRoute(): Route
    {
        return $this->route;
    }

    /**
     * Get route name
     *
     * @return string
     */
    public function getRouteName(): string
    {
        return $this->route->getName();
    }

    /**
     * Get original tag
     *
     * @return \Spatie\Sitemap\Tags\Tag
     */
    public function getTag(): \Spatie\Sitemap\Tags\Tag
    {
        return $this->tag;
    }
}
