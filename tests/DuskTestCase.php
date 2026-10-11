<?php

namespace Tests;

use Dcat\Admin\Models\Administrator;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication, BrowserExtension, InteractsWithDatabase;

    /**
     * @var Administrator
     */
    protected $user;

    /**
     * @var bool
     */
    protected $login = true;

    public function login(Browser $browser)
    {
        $browser->loginAs($this->getUser(), 'admin');
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->extendBrowser();

        $this->boot();
    }

    public function tearDown(): void
    {
        $this->destory();

        parent::tearDown();
    }

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare()
    {
        // 允许通过设置 DUSK_EXTERNAL_DRIVER=1 连接外部已启动的 chromedriver
        if (getenv('DUSK_EXTERNAL_DRIVER')) {
            return;
        }

        // Dusk 8 启动 chromedriver 时不再默认指定端口（默认 9511），
        // 而 driver() 连接的是 9515，这里显式对齐
        static::startChromeDriver(['--port=9515']);
    }

    /**
     * @param  \Facebook\WebDriver\Remote\RemoteWebDriver  $driver
     * @return \Laravel\Dusk\Browser
     */
    protected function newBrowser($driver)
    {
        $browser = (new Browser($driver))->resize(1566, 1080);

        $browser->resolver->prefix = 'html';

        if ($this->login) {
            $this->login($browser);
        }

        return $browser;
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless=new',
            '--window-size=1920,1080',
        ]);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY_W3C, $options
            )
        );
    }
}
