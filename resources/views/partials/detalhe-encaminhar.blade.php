<form class="form-grid" method="post" action="{{ url('/solicitacao-servicos/chamado/'.$chamado['id'].'/encaminhar') }}" id="form-encaminhar">
    @csrf
    @include('buriti-ui::select-busca', [
        'campo' => 'setor_id',
        'id' => 'enc-setor',
        'rotulo' => 'Setor de destino',
        'icone' => 'fa-sitemap',
        'opcoes' => collect($setores ?? [])
            ->filter(fn ($s) => ($s['id'] ?? 0) > 0)
            ->map(fn ($s) => [
                'id' => $s['id'],
                'nome' => $s['nome'],
                'meta' => count($s['responsaveis'] ?? []).' responsável(is)',
                'data' => ['resp' => $s['responsaveis'] ?? []],
            ])->values()->all(),
        'selecionado' => $chamado['setor_id'] ?? null,
        'placeholder' => 'Buscar setor…',
        'obrigatorio' => true,
        'vazio' => '— escolher setor —',
        'onchange' => "window.__solEncSetor && window.__solEncSetor(this)",
        'ajuda' => 'Obrigatório. Os responsáveis abaixo mudam conforme o setor.',
    ])
    <label class="buriti-select col-wide">
        <span class="buriti-select-rotulo"><i class="fa fa-user"></i> Responsável do setor (opcional)</span>
        <select name="responsavel_id" id="enc-resp" class="buriti-select-lista" size="5">
            <option value="0">— opcional —</option>
        </select>
        <small class="buriti-select-ajuda">Só as pessoas já ligadas a este setor — não a rede inteira.</small>
    </label>
    <label>Ou digite o nome <input name="responsavel" placeholder="Se a pessoa não estiver no setor"></label>
    <div class="acoes"><button class="botao" type="submit"><i class="fa fa-share"></i> Encaminhar</button></div>
</form>
<script>
(function () {
    window.__solEncSetor = function (sel) {
        var r = document.getElementById('enc-resp');
        if (!r) return;
        r.innerHTML = '<option value="0">— opcional —</option>';
        var o = sel.options[sel.selectedIndex];
        if (!o || !o.value) return;
        try {
            var data = JSON.parse(o.getAttribute('data-resp') || '[]');
            (data || []).forEach(function (x) {
                var op = document.createElement('option');
                op.value = x.user_id;
                op.textContent = x.nome || ('#' + x.user_id);
                r.appendChild(op);
            });
        } catch (e) {}
    };
    var sel = document.getElementById('enc-setor');
    if (sel && sel.value) window.__solEncSetor(sel);
})();
</script>
