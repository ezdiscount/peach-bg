<?php
namespace test\db;

use PHPUnit\Framework\TestCase;
use service\config\QuotaRate;
use service\config\UpgradeUserThreshold;

class ConfigTest extends TestCase
{
    public function testUpgradeUserThreshold()
    {
        $threshold = new UpgradeUserThreshold();
        echo PHP_EOL, $threshold->toString();
        $this->assertTrue(true);
    }

    public function testQuotaRate()
    {
        $quota = new QuotaRate();
        echo PHP_EOL, $quota->toString();
        $this->assertTrue(true);
    }
}
