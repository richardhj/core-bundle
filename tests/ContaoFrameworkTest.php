<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test;

use Contao\CoreBundle\Adapter\ConfigAdapter;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\ContaoFramework;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Tests the ContaoFramework class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Dominik Tomasi <https://github.com/dtomasi>
 */
class ContaoFrameworkTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        global $kernel;

        $kernel = $this->mockKernel(); // FIXME: remove once #259 has been merged

        $framework = $this->getContaoFramework(
            $kernel->getContainer(),
            $this->mockRouter('/')
        );

        $this->assertInstanceOf('Contao\\CoreBundle\\ContaoFramework', $framework);
    }

    /**
     * Tests initializing the framework with a front end request.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFrontendRequest()
    {
        global $kernel;

        $kernel = $this->mockKernel(); // FIXME: remove once #259 has been merged

        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $container = $kernel->getContainer();
        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);
        $container->get('request_stack')->push($request);

        $framework = $this->getContaoFramework($container, $this->mockRouter('/index.html'));
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertFalse(defined('BE_USER_LOGGED_IN'));
        $this->assertFalse(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertEquals('FE', TL_MODE);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
        $this->assertEquals('', TL_REFERER_ID);
        $this->assertEquals('index.html', TL_SCRIPT);
        $this->assertEquals('', TL_PATH);
        $this->assertEquals('en', $GLOBALS['TL_LANGUAGE']);
        $this->assertInstanceOf('Contao\\CoreBundle\\Session\\Attribute\\ArrayAttributeBag', $_SESSION['BE_DATA']);
        $this->assertInstanceOf('Contao\\CoreBundle\\Session\\Attribute\\ArrayAttributeBag', $_SESSION['FE_DATA']);
    }

    /**
     * Tests initializing the framework with a back end request.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBackendRequest()
    {
        global $kernel;

        $kernel = $this->mockKernel(); // FIXME: remove once #259 has been merged

        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');
        $request->setLocale('de');

        $container = $kernel->getContainer();
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $framework = $this->getContaoFramework($container, $this->mockRouter('/contao/install'));
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertTrue(defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertEquals('BE', TL_MODE);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
        $this->assertEquals('foobar', TL_REFERER_ID);
        $this->assertEquals('contao/install', TL_SCRIPT);
        $this->assertEquals('', TL_PATH);
        $this->assertEquals('de', $GLOBALS['TL_LANGUAGE']);
    }

    /**
     * Tests initializing the framework without a request.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWithoutRequest()
    {
        global $kernel;

        $kernel = $this->mockKernel(); // FIXME: remove once #259 has been merged

        $container = $kernel->getContainer();
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $container->set('request_stack', new RequestStack());

        $framework = $this->getContaoFramework($container, $this->mockRouter('/contao/install'));
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertTrue(defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertEquals('BE', TL_MODE);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
        $this->assertNull(TL_REFERER_ID);
        $this->assertEquals('console', TL_SCRIPT);
        $this->assertNull(TL_PATH);
    }

    /**
     * Tests initializing the framework without a scope.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWithoutScope()
    {
        global $kernel;

        $kernel = $this->mockKernel(); // FIXME: remove once #259 has been merged

        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $container = $kernel->getContainer();
        $container->get('request_stack')->push($request);

        $framework = $this->getContaoFramework($container, $this->mockRouter('/contao/install'));
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertFalse(defined('BE_USER_LOGGED_IN'));
        $this->assertFalse(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertNull(TL_MODE);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
        $this->assertEquals('foobar', TL_REFERER_ID);
        $this->assertEquals('contao/install', TL_SCRIPT);
        $this->assertEquals('', TL_PATH);
    }

    /**
     * Tests that the framework is not initialized twice.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testNotInitializedTwice()
    {
        global $kernel;

        $kernel = $this->mockKernel(); // FIXME: remove once #259 has been merged

        $request = new Request();
        $request->attributes->set('_route', 'contao_backend_install');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $container = $kernel->getContainer();
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        /** @var ContaoFramework|\PHPUnit_Framework_MockObject_MockObject $framework */
        $framework = $this
            ->getMockBuilder('Contao\\CoreBundle\\ContaoFramework')
            ->setConstructorArgs([
                $container,
                $this->mockRouter('/contao/install'),
                $this->mockSession(),
                $this->getRootDir() . '/app',
                new CsrfTokenManager(
                    $this->getMock('Symfony\\Component\\Security\\Csrf\\TokenGenerator\\TokenGeneratorInterface'),
                    $this->getMock('Symfony\\Component\\Security\\Csrf\\TokenStorage\\TokenStorageInterface')
                ),
                'contao_csrf_token',
                $this->mockConfig(),
                error_reporting()
            ])
            ->setMethods(['isInitialized'])
            ->getMock()
        ;

        $framework
            ->expects($this->any())
            ->method('isInitialized')
            ->willReturnOnConsecutiveCalls(false, true)
        ;

        $framework->initialize();
        $framework->initialize();
    }

    /**
     * Tests that the error level will get updated when configured.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorLevelOverride()
    {
        global $kernel;

        $kernel = $this->mockKernel(); // FIXME: remove once #259 has been merged

        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $container = $kernel->getContainer();
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $framework = $this->getContaoFramework($container, $this->mockRouter('/contao/install'));

        $errorReporting = error_reporting();
        error_reporting(E_ALL ^ E_USER_NOTICE);

        $this->assertNotEquals(
            $errorReporting,
            error_reporting(),
            'Test is invalid, error level has not changed.'
        );

        $framework->initialize();

        $this->assertEquals($errorReporting, error_reporting());

        error_reporting($errorReporting);
    }

    /**
     * Tests initializing the framework with a valid request token.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testValidRequestToken()
    {
        global $kernel;

        $kernel = $this->mockKernel(); // FIXME: remove once #259 has been merged

        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'foobar');

        $container = $kernel->getContainer();
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $tokenGenerator = $this->getMock(
            'Symfony\\Component\\Security\\Csrf\\TokenGenerator\\TokenGeneratorInterface',
            ['generateToken']
        );

        $tokenGenerator
            ->expects($this->any())
            ->method('generateToken')
            ->willReturn('foobar')
        ;

        $tokenManager = $this->getMock(
            'Symfony\\Component\\Security\\Csrf\\CsrfTokenManager',
            ['isTokenValid'],
            [
                $tokenGenerator,
                $this->getMock('Symfony\\Component\\Security\\Csrf\\TokenStorage\\TokenStorageInterface')
            ]
        );

        $tokenManager
            ->expects($this->any())
            ->method('isTokenValid')
            ->willReturn('true')
        ;

        $framework = $this->getContaoFramework($container, $this->mockRouter('/contao/install'), $tokenManager);
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertTrue(defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertEquals('BE', TL_MODE);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
        $this->assertEquals('', TL_REFERER_ID);
        $this->assertEquals('contao/install', TL_SCRIPT);
        $this->assertEquals('', TL_PATH);
   }

    /**
     * Tests initializing the framework with an invalid request token.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @expectedException \Contao\CoreBundle\Exception\InvalidRequestTokenException
     */
    public function testInvalidRequestToken()
    {
        global $kernel;

        $kernel = $this->mockKernel(); // FIXME: remove once #259 has been merged

        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->setMethod('POST');

        $container = $kernel->getContainer();
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $framework = $this->getContaoFramework($container, $this->mockRouter('/contao/install'));
        $framework->initialize();
   }

    /**
     * Tests initializing the framework with an incomplete installation.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @expectedException \Contao\CoreBundle\Exception\IncompleteInstallationException
     */
    public function testIncompleteInstallation()
    {
        global $kernel;

        $kernel = $this->mockKernel(); // FIXME: remove once #259 has been merged

        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $container = $kernel->getContainer();
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $config = $this->getMock('Contao\\CoreBundle\\Adapter\\ConfigAdapter', ['isComplete']);

        $config
            ->expects($this->any())
            ->method('isComplete')
            ->willReturn(false)
        ;

        $config
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'characterSet':
                        return 'UTF-8';

                    case 'timeZone':
                        return 'Europe/Berlin';

                    default:
                        return null;
                }
            });
        ;

        $framework = $this->getContaoFramework($container, $this->mockRouter('/contao/install'), null, $config);
        $framework->initialize();
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @param ContainerInterface             $container     The container object
     * @param RouterInterface                $router        The router object
     * @param CsrfTokenManagerInterface|null $tokenManager  An optional token manager
     * @param ConfigAdapter|null             $configAdatper An optional config adapter
     *
     * @return ContaoFramework The object instance
     */
    public function getContaoFramework(
        ContainerInterface $container,
        RouterInterface $router,
        CsrfTokenManagerInterface $tokenManager = null,
        ConfigAdapter $configAdatper = null
    ) {
        if (null === $tokenManager) {
            $tokenManager = new CsrfTokenManager(
                $this->getMock('Symfony\\Component\\Security\\Csrf\\TokenGenerator\\TokenGeneratorInterface'),
                $this->getMock('Symfony\\Component\\Security\\Csrf\\TokenStorage\\TokenStorageInterface')
            );
        }

        if (null === $configAdatper) {
            $configAdatper = $this->mockConfig();
        }

        $framework = new ContaoFramework(
            $container,
            $router,
            $this->mockSession(),
            $this->getRootDir() . '/app',
            $tokenManager,
            'contao_csrf_token',
            $configAdatper,
            error_reporting()
        );

        return $framework;
    }
}