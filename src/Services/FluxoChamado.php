<?php

namespace iEducar\Packages\BuritiSolicitacaoServicos\Services;

/**
 * Fluxo de chamado: abertura → triagem → atendimento → resolução → fechamento + feedback.
 */
final class FluxoChamado
{
    public const STATUS_ABERTO = 'aberto';

    public const STATUS_TRIAGEM = 'triagem';

    public const STATUS_ATENDIMENTO = 'em_atendimento';

    public const STATUS_AGUARDANDO = 'aguardando';

    public const STATUS_RESOLVIDO = 'resolvido';

    public const STATUS_FECHADO = 'fechado';

    public const STATUS_INDEFERIDO = 'indeferido';

    public const STATUS_CANCELADO = 'cancelado';

    /** @return list<string> */
    public static function todos(): array
    {
        return [
            self::STATUS_ABERTO,
            self::STATUS_TRIAGEM,
            self::STATUS_ATENDIMENTO,
            self::STATUS_AGUARDANDO,
            self::STATUS_RESOLVIDO,
            self::STATUS_FECHADO,
            self::STATUS_INDEFERIDO,
            self::STATUS_CANCELADO,
        ];
    }

    /** @return list<string> */
    public static function abertos(): array
    {
        return [
            self::STATUS_ABERTO,
            self::STATUS_TRIAGEM,
            self::STATUS_ATENDIMENTO,
            self::STATUS_AGUARDANDO,
        ];
    }

    public static function rotulo(string $status): string
    {
        return match ($status) {
            self::STATUS_TRIAGEM => 'Em triagem',
            self::STATUS_ATENDIMENTO => 'Em atendimento',
            self::STATUS_AGUARDANDO => 'Aguardando solicitante',
            self::STATUS_RESOLVIDO => 'Resolvido',
            self::STATUS_FECHADO => 'Fechado',
            self::STATUS_INDEFERIDO => 'Indeferido',
            self::STATUS_CANCELADO => 'Cancelado',
            default => 'Aberto',
        };
    }

    public static function tom(string $status): string
    {
        return match ($status) {
            self::STATUS_RESOLVIDO, self::STATUS_FECHADO => 'ok',
            self::STATUS_INDEFERIDO, self::STATUS_CANCELADO => 'fail',
            self::STATUS_AGUARDANDO, self::STATUS_TRIAGEM => 'warn',
            default => 'warn',
        };
    }

    public static function estaAberto(string $status): bool
    {
        return in_array($status, self::abertos(), true);
    }

    public static function podeResponder(string $status): bool
    {
        return self::estaAberto($status) || $status === self::STATUS_RESOLVIDO;
    }

    public static function podeEncaminhar(string $status): bool
    {
        return self::estaAberto($status);
    }

    public static function podeFechar(string $status): bool
    {
        return in_array($status, [self::STATUS_RESOLVIDO, self::STATUS_ATENDIMENTO, self::STATUS_AGUARDANDO], true);
    }

    public static function podeFeedback(string $status): bool
    {
        return in_array($status, [self::STATUS_RESOLVIDO, self::STATUS_FECHADO], true);
    }

    /**
     * @return list<string>
     */
    public static function proximos(string $atual): array
    {
        return match ($atual) {
            self::STATUS_ABERTO => [self::STATUS_TRIAGEM, self::STATUS_ATENDIMENTO, self::STATUS_INDEFERIDO, self::STATUS_CANCELADO],
            self::STATUS_TRIAGEM => [self::STATUS_ATENDIMENTO, self::STATUS_AGUARDANDO, self::STATUS_INDEFERIDO, self::STATUS_CANCELADO],
            self::STATUS_ATENDIMENTO => [self::STATUS_AGUARDANDO, self::STATUS_RESOLVIDO, self::STATUS_TRIAGEM, self::STATUS_CANCELADO],
            self::STATUS_AGUARDANDO => [self::STATUS_ATENDIMENTO, self::STATUS_RESOLVIDO, self::STATUS_CANCELADO],
            self::STATUS_RESOLVIDO => [self::STATUS_FECHADO, self::STATUS_ATENDIMENTO],
            default => [],
        };
    }

    public static function podeTransitar(string $de, string $para): bool
    {
        return in_array($para, self::proximos($de), true);
    }
}
