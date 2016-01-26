<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace ZfrOAuth2Test\Server\Container;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Interop\Container\ContainerInterface;
use ZfrOAuth2\Server\Container\AccessTokenServiceFactory;
use ZfrOAuth2\Server\Entity\AccessToken;
use ZfrOAuth2\Server\Options\ServerOptions;
use ZfrOAuth2\Server\Service\ScopeService;
use ZfrOAuth2\Server\Service\TokenService;
use ZfrOAuth2Test\Server\Service\TokenServiceTest;

/**
 * @author  Michaël Gallego <mic.gallego@gmail.com>
 * @licence MIT
 * @covers  \ZfrOAuth2\Server\Container\AccessTokenServiceFactory
 */
class AccessTokenServiceFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCanCreateFromFactory()
    {
        $container = $this->getMock(ContainerInterface::class);

        $serverOptions = new ServerOptions(['object_manager' => 'my_object_manager']);

        $objectManager = $this->getMock(ObjectManager::class);
        $objectManager->expects($this->at(0))
            ->method('getRepository')
            ->with(AccessToken::class)
            ->willReturn($this->getMock(ObjectRepository::class));

        $managerRegistry = $this->getMock(ManagerRegistry::class, [], [], '', false);
        $managerRegistry->expects($this->once())
            ->method('getManager')
            ->with($serverOptions->getObjectManager())
            ->willReturn($objectManager);

        $container->expects($this->at(0))
            ->method('get')
            ->with(ManagerRegistry::class)
            ->willReturn($managerRegistry);

        $container->expects($this->at(1))
            ->method('get')
            ->with(ServerOptions::class)
            ->willReturn($serverOptions);

        $container->expects($this->at(2))
            ->method('get')
            ->with(ScopeService::class)
            ->willReturn($this->getMock(ScopeService::class, [], [], '', false));

        $factory = new AccessTokenServiceFactory();
        $service = $factory($container);

        $this->assertInstanceOf(TokenService::class, $service);
    }
}