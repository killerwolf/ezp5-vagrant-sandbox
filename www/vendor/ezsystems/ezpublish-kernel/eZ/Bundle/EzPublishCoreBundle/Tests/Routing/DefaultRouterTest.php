<?php
/**
 * File containing the DefaultRouterTest class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version 2014.07.0
 */

namespace eZ\Bundle\EzPublishCoreBundle\Tests\Routing;

use eZ\Bundle\EzPublishCoreBundle\Routing\DefaultRouter;
use eZ\Publish\Core\MVC\Symfony\Routing\SimplifiedRequest;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use ReflectionObject;

class DefaultRouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    private $configResolver;

    /**
     * @var \Symfony\Component\Routing\RequestContext
     */
    private $requestContext;

    protected function setUp()
    {
        parent::setUp();
        $this->container = $this->getMock( 'Symfony\\Component\\DependencyInjection\\ContainerInterface' );
        $this->configResolver = $this->getMock( 'eZ\\Publish\\Core\\MVC\\ConfigResolverInterface' );
        $this->requestContext = new RequestContext();
    }

    /**
     * @param array $mockedMethods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|DefaultRouter
     */
    private function generateRouter( array $mockedMethods = array() )
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DefaultRouter $router */
        $router = $this
            ->getMockBuilder( 'eZ\\Bundle\\EzPublishCoreBundle\\Routing\\DefaultRouter' )
            ->setConstructorArgs( array( $this->container, 'foo', array(), $this->requestContext ) )
            ->setMethods( array_merge( $mockedMethods ) )
            ->getMock();
        $router->setConfigResolver( $this->configResolver );
        return $router;
    }

    public function testMatchRequestWithSemanticPathinfo()
    {
        $pathinfo = '/siteaccess/foo/bar';
        $semanticPathinfo = '/foo/bar';
        $request = Request::create( $pathinfo );
        $request->attributes->set( 'semanticPathinfo', $semanticPathinfo );

        /** @var \PHPUnit_Framework_MockObject_MockObject|DefaultRouter $router */
        $router = $this->generateRouter( array( 'match' ) );

        $matchedParameters = array( '_controller' => 'AcmeBundle:myAction' );
        $router
            ->expects( $this->once() )
            ->method( 'match' )
            ->with( $semanticPathinfo )
            ->will( $this->returnValue( $matchedParameters ) );
        $this->assertSame( $matchedParameters, $router->matchRequest( $request ) );
    }

    public function testMatchRequestRegularPathinfo()
    {
        $matchedParameters = array( '_controller' => 'AcmeBundle:myAction' );
        $pathinfo = '/siteaccess/foo/bar';

        $request = Request::create( $pathinfo );

        $this->configResolver->expects( $this->never() )->method( 'getParameter' );

        /** @var \PHPUnit_Framework_MockObject_MockObject|DefaultRouter $router */
        $router = $this->generateRouter( array( 'match' ) );
        $router
            ->expects( $this->once() )
            ->method( 'match' )
            ->with( $pathinfo )
            ->will( $this->returnValue( $matchedParameters ) );
        $this->assertSame( $matchedParameters, $router->matchRequest( $request ) );
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testMatchRequestLegacyMode()
    {
        $pathinfo = '/siteaccess/foo/bar';
        $semanticPathinfo = '/foo/bar';
        $request = Request::create( $pathinfo );
        $request->attributes->set( 'semanticPathinfo', $semanticPathinfo );

        /** @var \PHPUnit_Framework_MockObject_MockObject|DefaultRouter $router */
        $router = $this->generateRouter( array( 'match' ) );

        $this->configResolver
            ->expects( $this->once() )
            ->method( 'getParameter' )
            ->with( 'legacy_mode' )
            ->will( $this->returnValue( true ) );

        $matchedParameters = array( '_route' => 'my_route' );
        $router
            ->expects( $this->once() )
            ->method( 'match' )
            ->with( $semanticPathinfo )
            ->will( $this->returnValue( $matchedParameters ) );

        $router->matchRequest( $request );
    }

    public function testMatchRequestLegacyModeAuthorizedRoute()
    {
        $pathinfo = '/siteaccess/foo/bar';
        $semanticPathinfo = '/foo/bar';
        $request = Request::create( $pathinfo );
        $request->attributes->set( 'semanticPathinfo', $semanticPathinfo );

        /** @var \PHPUnit_Framework_MockObject_MockObject|DefaultRouter $router */
        $router = $this->generateRouter( array( 'match' ) );
        $router->setLegacyAwareRoutes( array( 'my_legacy_aware_route' ) );

        $matchedParameters = array( '_route' => 'my_legacy_aware_route' );
        $router
            ->expects( $this->once() )
            ->method( 'match' )
            ->with( $semanticPathinfo )
            ->will( $this->returnValue( $matchedParameters ) );

        $this->configResolver->expects( $this->never() )->method( 'getParameter' );

        $this->assertSame( $matchedParameters, $router->matchRequest( $request ) );
    }

    /**
     * @dataProvider providerGenerateNoSiteAccess
     */
    public function testGenerateNoSiteAccess( $url )
    {
        $generator = $this->getMock( 'Symfony\\Component\\Routing\\Generator\\UrlGeneratorInterface' );
        $generator
            ->expects( $this->once() )
            ->method( 'generate' )
            ->with( __METHOD__ )
            ->will( $this->returnValue( $url ) );

        /** @var DefaultRouter|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->generateRouter( array( 'getGenerator' ) );
        $router
            ->expects( $this->any() )
            ->method( 'getGenerator' )
            ->will( $this->returnValue( $generator ) );

        $this->assertSame( $url, $router->generate( __METHOD__ ) );
    }

    public function providerGenerateNoSiteAccess()
    {
        return array(
            array( '/foo/bar' ),
            array( '/foo/bar/baz?truc=muche&tata=toto' ),
            array( 'http://ez.no/Products/eZ-Publish-CMS' ),
            array( 'http://www.metalfrance.net/decouvertes/edge-caress-inverse-ep' ),
        );
    }

    /**
     * @dataProvider providerGenerateWithSiteAccess
     *
     * @param string $urlGenerated The URL generated by the standard UrLGenerator
     * @param string $relevantUri The relevant URI part of the generated URL (without host and basepath)
     * @param string $expectedUrl The URL we're expecting to be finally generated, with siteaccess
     * @param string $saName The SiteAccess name
     * @param bool $isMatcherLexer True if the siteaccess matcher is URILexer
     * @param bool $absolute True if generated link needs to be absolute
     * @param string $routeName
     */
    public function testGenerateWithSiteAccess( $urlGenerated, $relevantUri, $expectedUrl, $saName, $isMatcherLexer, $absolute, $routeName )
    {
        $routeName = $routeName ?: __METHOD__;
        $nonSiteAccessAwareRoutes = array( '_dontwantsiteaccess' );
        $generator = $this->getMock( 'Symfony\\Component\\Routing\\Generator\\UrlGeneratorInterface' );
        $generator
            ->expects( $this->once() )
            ->method( 'generate' )
            ->with( $routeName )
            ->will( $this->returnValue( $urlGenerated ) );

        /** @var DefaultRouter|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->generateRouter( array( 'getGenerator' ) );
        $router
            ->expects( $this->any() )
            ->method( 'getGenerator' )
            ->will( $this->returnValue( $generator ) );

        // If matcher is URILexer, we make it act as it's supposed to, prepending the siteaccess.
        if ( $isMatcherLexer )
        {
            $matcher = $this->getMock( 'eZ\\Publish\\Core\\MVC\\Symfony\\SiteAccess\\URILexer' );
            // Route is siteaccess aware, we're expecting analyseLink() to be called
            if ( !in_array( $routeName, $nonSiteAccessAwareRoutes ) )
            {
                $matcher
                    ->expects( $this->once() )
                    ->method( 'analyseLink' )
                    ->with( $relevantUri )
                    ->will( $this->returnValue( "/$saName$relevantUri" ) );
            }
            // Non-siteaccess aware route, it's not supposed to be analysed
            else
            {
                $matcher
                    ->expects( $this->never() )
                    ->method( 'analyseLink' );
            }
        }
        else
        {
            $matcher = $this->getMock( 'eZ\\Publish\\Core\\MVC\\Symfony\\SiteAccess\\Matcher' );
        }

        $sa = new SiteAccess( $saName, 'test', $matcher );
        $router->setSiteAccess( $sa );

        $requestContext = new RequestContext();
        $urlComponents = parse_url( $urlGenerated );
        if ( isset( $urlComponents['host'] ) )
        {
            $requestContext->setHost( $urlComponents['host'] );
            $requestContext->setScheme( $urlComponents['scheme'] );
            if ( isset( $urlComponents['port'] ) && $urlComponents['scheme'] === 'http' )
                $requestContext->setHttpPort( $urlComponents['port'] );
            else if ( isset( $urlComponents['port'] ) && $urlComponents['scheme'] === 'https' )
                $requestContext->setHttpsPort( $urlComponents['port'] );
        }
        $requestContext->setBaseUrl(
            substr( $urlComponents['path'], 0, strpos( $urlComponents['path'], $relevantUri ) )
        );
        $router->setContext( $requestContext );
        $router->setNonSiteAccessAwareRoutes( $nonSiteAccessAwareRoutes );

        $this->assertSame( $expectedUrl, $router->generate( $routeName, array(), $absolute ) );
    }

    public function providerGenerateWithSiteAccess()
    {
        return array(
            array( '/foo/bar', '/foo/bar', '/foo/bar', 'test_siteaccess', false, false, null ),
            array( 'http://ezpublish.dev/foo/bar', '/foo/bar', 'http://ezpublish.dev/foo/bar', 'test_siteaccess', false, true, null ),
            array( 'http://ezpublish.dev/foo/bar', '/foo/bar', 'http://ezpublish.dev/test_siteaccess/foo/bar', 'test_siteaccess', true, true, null ),
            array( 'http://ezpublish.dev/foo/bar', '/foo/bar', 'http://ezpublish.dev/foo/bar', 'test_siteaccess', true, true, '_dontwantsiteaccess' ),
            array( 'http://ezpublish.dev:8080/foo/bar', '/foo/bar', 'http://ezpublish.dev:8080/test_siteaccess/foo/bar', 'test_siteaccess', true, true, null ),
            array( 'http://ezpublish.dev:8080/foo/bar', '/foo/bar', 'http://ezpublish.dev:8080/foo/bar', 'test_siteaccess', true, true, '_dontwantsiteaccess' ),
            array( 'https://ezpublish.dev/secured', '/secured', 'https://ezpublish.dev/test_siteaccess/secured', 'test_siteaccess', true, true, null ),
            array( 'https://ezpublish.dev:445/secured', '/secured', 'https://ezpublish.dev:445/test_siteaccess/secured', 'test_siteaccess', true, true, null ),
            array( 'http://ezpublish.dev:8080/foo/root_folder/bar/baz', '/bar/baz', 'http://ezpublish.dev:8080/foo/root_folder/test_siteaccess/bar/baz', 'test_siteaccess', true, true, null ),
            array( '/foo/bar/baz', '/foo/bar/baz', '/test_siteaccess/foo/bar/baz', 'test_siteaccess', true, false, null ),
            array( '/foo/root_folder/bar/baz', '/bar/baz', '/foo/root_folder/test_siteaccess/bar/baz', 'test_siteaccess', true, false, null ),
            array( '/foo/bar/baz', '/foo/bar/baz', '/foo/bar/baz', 'test_siteaccess', true, false, '_dontwantsiteaccess' ),
        );
    }

    public function testGenerateReverseSiteAccessMatch()
    {
        $routeName = 'some_route_name';
        $urlGenerated = 'http://phoenix-rises.fm/foo/bar';

        $siteAccessName = 'foo_test';
        $siteAccessRouter = $this->getMock( 'eZ\Publish\Core\MVC\Symfony\SiteAccess\SiteAccessRouterInterface' );
        $versatileMatcher = $this->getMock( 'eZ\Publish\Core\MVC\Symfony\SiteAccess\VersatileMatcher' );
        $simplifiedRequest = new SimplifiedRequest(
            array(
                'host' => 'phoenix-rises.fm',
                'scheme' => 'http'
            )
        );
        $versatileMatcher
            ->expects( $this->once() )
            ->method( 'getRequest' )
            ->will( $this->returnValue( $simplifiedRequest ) );
        $siteAccessRouter
            ->expects( $this->once() )
            ->method( 'matchByName' )
            ->with( $siteAccessName )
            ->will( $this->returnValue( new SiteAccess( $siteAccessName, 'foo', $versatileMatcher ) ) );

        $generator = $this->getMock( 'Symfony\Component\Routing\Generator\UrlGeneratorInterface' );
        $generator
            ->expects( $this->at( 0 ) )
            ->method( 'setContext' )
            ->with( $this->isInstanceOf( 'Symfony\Component\Routing\RequestContext' ) );
        $generator
            ->expects( $this->at( 1 ) )
            ->method( 'generate' )
            ->with( $routeName )
            ->will( $this->returnValue( $urlGenerated ) );
        $generator
            ->expects( $this->at( 2 ) )
            ->method( 'setContext' )
            ->with( $this->requestContext );

        $router = new DefaultRouter( $this->container, 'foo', array(), $this->requestContext );
        $router->setConfigResolver( $this->configResolver );
        $router->setSiteAccess( new SiteAccess( 'test', 'test', $this->getMock( 'eZ\Publish\Core\MVC\Symfony\SiteAccess\Matcher' ) ) );
        $router->setSiteAccessRouter( $siteAccessRouter );
        $refRouter = new ReflectionObject( $router );
        $refGenerator = $refRouter->getProperty( 'generator' );
        $refGenerator->setAccessible( true );
        $refGenerator->setValue( $router, $generator );

        $this->assertSame(
            $urlGenerated,
            $router->generate( $routeName, array( 'siteaccess' => $siteAccessName ), DefaultRouter::ABSOLUTE_PATH )
        );
    }
}
