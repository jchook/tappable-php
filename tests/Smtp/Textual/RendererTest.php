<?php

namespace Tap\Smtp\Test;

use PHPUnit\Framework\TestCase;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\Command\Helo;
use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\Command\RcptTo;
use Tap\Smtp\Element\ForwardPath;
use Tap\Smtp\Element\Mailbox;
use Tap\Smtp\Element\OriginAddressLiteral;
use Tap\Smtp\Element\OriginDomain;
use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Textual\Renderer;

class RendererTest extends TestCase
{
  public function testRenderOriginAddressLiteral()
  {
    $ip4 ='127.0.0.1';
    $ip6 = 'ff00::1';
    $o4 = new OriginAddressLiteral($ip4);
    $o6 = new OriginAddressLiteral($ip6);
    $renderer = new Renderer(smtputf8: true);
    $this->assertSame(
      "[$ip4]",
      $renderer->renderOrigin($o4),
    );
    $this->assertSame(
      "[$ip6]",
      $renderer->renderOrigin($o6),
    );
  }

  public function testRenderOriginDomain()
  {
    $domainA = 'regular.domain';
    $domainU = '🦆.ducks.gov';
    $domainUA = idn_to_ascii($domainU);
    $oA = new OriginDomain($domainA);
    $oU = new OriginDomain($domainU);
    $rA = new Renderer(smtputf8: false);
    $rU = new Renderer(smtputf8: true);
    $this->assertSame(
      $domainA,
      $rA->renderOrigin($oA),
    );
    $this->assertSame(
      $domainU,
      $rU->renderOrigin($oU),
    );
    $this->assertSame(
      $domainUA,
      $rA->renderOrigin($oU),
      'Automatic IDN conversion for non-smtpUTF8',
    );
  }

  public function testRenderHelo()
  {
    $ip ='127.0.0.1';
    $ipOrigin = new OriginAddressLiteral($ip);
    $domain ='🦆.ducks.gov';
    $domainAscii = idn_to_ascii($domain);
    $domainOrigin = new OriginDomain($domain);
    $helo = new Helo($domainOrigin);
    $ehlo = new Ehlo($ipOrigin);
    $renderer = new Renderer(smtputf8: false);
    $this->assertSame(
      "HELO $domainAscii\r\n",
      $renderer->renderCommand($helo)
    );
    $renderer = new Renderer(smtputf8: true);
    $this->assertSame(
      "HELO $domain\r\n",
      $renderer->renderCommand($helo)
    );
    $this->assertSame(
      "EHLO [$ip]\r\n",
      $renderer->renderCommand($ehlo)
    );
  }

  public function testRenderMailRctp()
  {
    $localPart = 'mister🦆';
    $domain = '🦆.ducks.gov';
    $domainAscii = idn_to_ascii($domain);
    $origin = new OriginDomain($domain);
    $mailbox = new Mailbox($localPart, $origin);
    $mailFrom = new MailFrom(new ReversePath($mailbox));
    $rcptTo = new RcptTo(new ForwardPath($mailbox));
    $renderer = new Renderer(smtputf8: true);
    $this->assertSame(
      "MAIL FROM:<\"{$localPart}\"@{$domain}>\r\n",
      $renderer->renderCommand($mailFrom)
    );
    $renderer = new Renderer(smtputf8: false);
    $this->assertSame(
      "MAIL FROM:<\"{$localPart}\"@{$domainAscii}>\r\n",
      $renderer->renderCommand($mailFrom)
    );
  }
}


