<?php

namespace Tests\AppBundle\Service;

use AppBundle\Service\SettingsManager;
use Craue\ConfigBundle\Util\Config as CraueConfig;
use Craue\ConfigBundle\Entity\Setting;
use Doctrine\Persistence\ManagerRegistry;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class SettingsManagerTest extends TestCase
{
    private $innerGeocoder;

    public function setUp(): void
    {
        $this->craueConfig = $this->prophesize(CraueConfig::class);
        $this->doctrine = $this->prophesize(ManagerRegistry::class);
        $this->phoneNumberUtil = $this->prophesize(PhoneNumberUtil::class);

        $this->settingsManager = new SettingsManager(
            $this->craueConfig->reveal(),
            Setting::class,
            $this->doctrine->reveal(),
            $this->phoneNumberUtil->reveal(),
            'fr',
            true,
            new NullLogger()
        );
    }

    public function canSendSmsProvider()
    {
        return [
            [
                false,
                false,
                null,
                null,
                'fr'
            ],
            [
                false,
                true,
                'foo',
                null,
                'fr'
            ],
            [
                false,
                true,
                'mailjet',
                null,
                'fr'
            ],
            [
                false,
                true,
                'mailjet',
                json_encode(['foo' => 'bar']),
                'fr'
            ],
            [
                true,
                true,
                'mailjet',
                json_encode(['api_token' => 'bar']),
                'fr'
            ],
            [
                false,
                true,
                'mailjet',
                json_encode(['api_token' => 'bar']),
                'gb'
            ],
        ];
    }

    /**
     * @dataProvider canSendSmsProvider
     */
    public function testCanSendSms($expected, $smsEnabled, $smsGateway, $smsGatewayConfig, $country)
    {
        $this->craueConfig->get('sms_enabled')->willReturn($smsEnabled);
        $this->craueConfig->get('sms_gateway')->willReturn($smsGateway);
        $this->craueConfig->get('sms_gateway_config')->willReturn($smsGatewayConfig);

        $settingsManager = new SettingsManager(
            $this->craueConfig->reveal(),
            Setting::class,
            $this->doctrine->reveal(),
            $this->phoneNumberUtil->reveal(),
            $country,
            true,
            new NullLogger()
        );

        $this->assertEquals($expected, $settingsManager->canSendSms());
    }
}
