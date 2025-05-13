<!-- MENU FIXO AVANÇADO PARA MOBILE -->
<style>
.fixed-nav {
    position: fixed;
    bottom: 0;
    width: 100%;
    background: #ffffff;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-around;
    padding: 10px 0;
    z-index: 9999;
    border-top: 1px solid #dee2e6;
}
.fixed-nav a {
    flex: 1;
    text-align: center;
    font-size: 12px;
    text-decoration: none;
    color: #444;
}
.fixed-nav a span {
    display: block;
    margin-top: 4px;
}
.fixed-nav a:hover {
    background-color: #f8f9fa;
}
@media (min-width: 768px) {
    .fixed-nav { display: none; }
}
</style>

<div class="fixed-nav">
    <a href="index.php">
        <div>🏠<span>Resumo</span></div>
    </a>
    <a href="ganhos.php">
        <div>💵<span>Ganho</span></div>
    </a>
    <a href="nova_atividade.php">
        <div>➕<span>Atividade</span></div>
    </a>
    <a href="listar_ganhos.php">
        <div>📄<span>Ganhos</span></div>
    </a>
</div>