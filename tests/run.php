#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__.'/../src/Services/ChamadoSla.php';
require __DIR__.'/../src/Services/FluxoChamado.php';
require __DIR__.'/../src/Services/FluxoEscolaSme.php';

$ok=0; $fail=0;
$assert = static function(bool $c, string $m) use (&$ok,&$fail): void {
  echo ($c? '  OK  ':' FAIL ').$m."\n";
  $c ? $ok++ : $fail++;
};

$slaOk = \iEducar\Packages\BuritiSolicitacaoServicos\Services\ChamadoSla::dentroDoPrazo(['prioridade'=>'alta','aberto_em'=>'2026-01-01T00:00:00','resolvido_em'=>'2026-01-01T10:00:00']);
$assert($slaOk===true, 'SLA dentro');
$assert(
  \iEducar\Packages\BuritiSolicitacaoServicos\Services\FluxoEscolaSme::proximoStatus('aberto') === 'triagem',
  'fluxo aberto→triagem'
);

echo "\nResultado: $ok ok, $fail falhas\n";
exit($fail>0?1:0);
