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

namespace ZfrOAuth2Test\Server\Service;

use ZfrOAuth2\Server\Entity\AbstractToken;
use ZfrOAuth2\Server\Entity\AccessToken;
use ZfrOAuth2\Server\Entity\Scope;
use ZfrOAuth2\Server\Exception\OAuth2Exception;
use ZfrOAuth2\Server\Repository\TokenRepositoryInterface;
use ZfrOAuth2\Server\Service\ScopeService;
use ZfrOAuth2\Server\Service\TokenService;

/**
 * @author  Michaël Gallego <mic.gallego@gmail.com>
 * @licence MIT
 * @covers \ZfrOAuth2\Server\Service\TokenService
 */
class TokenServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TokenRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenRepository;

    /**
     * @var ScopeService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeService;

    /**
     * @var TokenService
     */
    protected $tokenService;

    public function setUp()
    {
        $this->tokenRepository = $this->getMock(TokenRepositoryInterface::class);
        $this->scopeService    = $this->getMock(ScopeService::class, [], [], '', false);
        $this->tokenService    = new TokenService(
            $this->tokenRepository,
            $this->scopeService
        );
    }

    public function testCanGetToken()
    {
        $token = new AccessToken();
        $token->setToken('token');

        $this->tokenRepository->expects($this->once())
                              ->method('findByToken')
                              ->with('token')
                              ->will($this->returnValue($token));

        $this->assertSame($token, $this->tokenService->getToken('token'));
    }

    public function testGetTokenReturnNullOnTokenNotFound()
    {
        $this->tokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->with('token');

        $this->assertNull($this->tokenService->getToken('token'));
    }

    public function testDoesCaseSensitiveTest()
    {
        $token = new AccessToken();
        $token->setToken('Token');

        $this->tokenRepository->expects($this->once())
                              ->method('findByToken')
                              ->with('token')
                              ->will($this->returnValue($token));

        $this->assertNull($this->tokenService->getToken('token'));
    }

    public function scopeProvider()
    {
        return [
            // With no scope
            [
                'registered_scopes' => ['read', 'write'],
                'token_scope'       => '',
                'throw_exception'   => false
            ],
            // With less permissions
            [
                'registered_scopes' => ['read', 'write'],
                'token_scope'       => 'read',
                'throw_exception'   => false
            ],
            // With same permissions
            [
                'registered_scopes' => ['read', 'write'],
                'token_scope'       => 'read write',
                'throw_exception'   => false
            ],
            // With too much permissions
            [
                'registered_scopes' => ['read', 'write'],
                'token_scope'       => 'read write delete',
                'throw_exception'   => true
            ],
        ];
    }

    /**
     * @dataProvider scopeProvider
     */
    public function testCanSaveToken($registeredScopes, $tokenScope, $throwException)
    {
        if ($throwException) {
            $this->setExpectedException(OAuth2Exception::class, null, 'invalid_scope');
        }

        $token = new AccessToken();

        if (empty($tokenScope)) {
            $scope = new Scope();
            $scope->setName('read');

            $this->scopeService->expects($this->once())
                               ->method('getDefaultScopes')
                               ->will($this->returnValue([$scope]));
        } else {
            $token->setScopes($tokenScope);
        }

        if (!$throwException) {
            $this->tokenRepository->expects($this->once())
                                  ->method('save')
                                  ->with($this->isInstanceOf(AbstractToken::class));
        }

        $scopes = [];
        foreach ($registeredScopes as $registeredScope) {
            $scope = new Scope();
            $scope->setName($registeredScope);

            $scopes[] = $scope;
        }

        $this->scopeService->expects($this->any())->method('getAll')->will($this->returnValue($scopes));

        $this->tokenService->createToken($token);

        $this->assertEquals(40, strlen($token->getToken()));

        if (empty($tokenScope)) {
            $this->assertCount(1, $token->getScopes());
        } else {
            $this->assertEquals(explode(' ', $tokenScope), $token->getScopes());
        }
    }

    public function testCreateNewTokenUntilOneDoesNotExist()
    {
        $token = new AccessToken();

        $this->scopeService->expects($this->once())->method('getDefaultScopes')->will($this->returnValue(['read']));

        $this->tokenRepository->expects($this->at(0))
                              ->method('findByToken')
                              ->with($this->isType('string'))
                              ->will($this->returnValue(new AccessToken()));

        $this->tokenRepository->expects($this->at(1))
                              ->method('findByToken')
                              ->with($this->isType('string'))
                              ->will($this->returnValue(null));

        $this->tokenService->createToken($token);

        $this->assertEquals(40, strlen($token->getToken()));
    }
}
