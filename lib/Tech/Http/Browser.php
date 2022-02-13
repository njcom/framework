<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Http;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy as By;
use Facebook\WebDriver\WebDriverExpectedCondition;

class Browser extends RemoteWebDriver {
    public const WEB_DRIVER_URI = 'http://localhost:4444';
    protected const WAIT_TIMEOUT = 20;
    // sec
    protected const WAIT_INTERVAL = 1000;
    // ms
    protected const CONNECTION_TIMEOUT = 30000;
    // ms, corresponds to CURLOPT_CONNECTTIMEOUT_MS
    protected const REQUEST_TIMEOUT = 30000;
    // ms, corresponds to CURLOPT_TIMEOUT_MS
    /**
     * Timeout in sec, how long to wait() for condition
     */
    private int $waitTimeout = self::WAIT_TIMEOUT;
    /**
     * Interval in ms, how often check for condition in wait()
     */
    private int $waitInterval = self::WAIT_INTERVAL;

    /**
     * @param DesiredCapabilities|array $desiredCapabilities
     * @param string|null $webDriverUri
     * @return Browser
     */
    public static function mk($desiredCapabilities, string $webDriverUri = null): Browser {
        if (null === $webDriverUri) {
            $webDriverUri = self::WEB_DRIVER_URI;
        }
        return static::create($webDriverUri, $desiredCapabilities, self::CONNECTION_TIMEOUT, self::REQUEST_TIMEOUT);
        /*
        // @var \Facebook\WebDriver\WebDriverTimeouts
        $timeouts = $browser->manage()->timeouts();
        $timeouts->implicitlyWait(10);
            ->setScriptTimeout()
            ->pageLoadTimeout();
        */
    }

    public function setWaitTimeout(int $timeout): static {
        $this->waitTimeout = $timeout;
        return $this;
    }

    public function waitTimeout(): int {
        return $this->waitTimeout;
    }

    public function setWaitInterval(int $interval): static {
        $this->waitInterval = $interval;
        return $this;
    }

    public function waitInterval(): int {
        return $this->waitInterval;
    }

    public function fillForm(iterable $formValues): void {
        foreach ($formValues as $name => $value) {
            $this->findElement(By::name($name))->sendKeys($value);
        }
    }

    public function waitUntilTitleIsEqual(string $expectedTitle): void {
        $this->waitUntil(WebDriverExpectedCondition::titleIs($expectedTitle));
    }

    /**
     * @param callable|WebDriverExpectedCondition $predicate
     * @param string $message
     * @return mixed
     */
    public function waitUntil($predicate, $message = '') {
        return $this->wait($this->waitTimeout, $this->waitInterval)->until($predicate, $message);
    }

    public function waitUntilElementIsVisible(By $selector): void {
        $this->waitUntil(WebDriverExpectedCondition::visibilityOfElementLocated($selector));
    }
    /*
    protected function waitEnterKey() {
        // http://codeception.com/11-12-2013/working-with-phpunit-and-selenium-webdriver.html
        if (trim(fgets(fopen("php://stdin","r"))) != chr(13)) {
    
        }
    }
    */
}