<div class="help">

<a id="help-storage-pool"><h1><?php echo __(sfConfig::get('app_storage_pool_title')) ?></h1></a>
<p>Informação relativa às ligações de <em><?php echo __(sfConfig::get('app_storage_pool_title')) ?></em> e parâmetros de configuração. 
    Nesta grelha é possível fazer a administração de <em><?php echo __(sfConfig::get('app_storage_pool_title')) ?></em>, nomeadamente as seguintes operações:</p>

<ul>
    <li><a href="#help-storage-pool-new">Criar <em><?php echo __(sfConfig::get('app_storage_pool_title')) ?></em>;</a></li>
    <li><a href="#help-storage-pool-reload">Recarregar <em><?php echo __(sfConfig::get('app_storage_pool_title')) ?></em>;</a></li>
    <li><a href="#help-storage-pool-remove">Remover <em><?php echo __(sfConfig::get('app_storage_pool_title')) ?></em>.</a></li>
</ul>

<br/>
<hr/>

<a id="help-storage-pool-new"><h1>Criar <em><b><?php echo __(sfConfig::get('app_storage_pool_title')) ?></b></em></h1></a>
<p>Para criar uma nova ligação de <em><?php echo __(sfConfig::get('app_storage_pool_title')) ?></em> acedemos ao menu <em>Nova <?php echo __(sfConfig::get('app_storage_pool_title')) ?></em>, preenchemos os campos nome e endereço <em>IP</em> e procedemos ao <em>discovery</em> para completar o preenchimento do campo <em>Target IQN</em>. Confirmamos todos os dados de configuração e fazemos <em>Guardar</em> para criar a configuração nos agentes.</p>

<a href="#help-storage-pool"><div>Início</div></a>
<hr/>

<a id="help-storage-pool-reload"><h1>Recarregar <em><b><?php echo __(sfConfig::get('app_storage_pool_title')) ?></b></em></h1></a>
<p> Em determinados casos, pode ser necessário configurar uma ligação à <em><?php echo __(sfConfig::get('app_storage_pool_title')) ?></em> num novo agente que foi adicionado ao <em>datacenter</em> recentemente ou efectuar um <em>refresh</em> à <em><?php echo __(sfConfig::get('app_storage_pool_title')) ?></em> para fazer login novamente. Para tal, usamos a opção <em>Recarregar <?php echo __(sfConfig::get('app_storage_pool_title')) ?></em>, acendo ao menu de contexto da linha da grelha respectiva à ligação que pretendemos executar a operação.</p>

<a href="#help-storage-pool"><div>Início</div></a>
<hr/>

<a id="help-storage-pool-remove"><h1>Remover <em><b><?php echo __(sfConfig::get('app_storage_pool_title')) ?></b></em></h1></a>
<p> De igual forma, para remover uma ligação <em><?php echo __(sfConfig::get('app_storage_pool_title')) ?></em>, acedemos ao menu de contexto da linha que pretendemos remover e escolhemos a opção <em>Remover <?php echo __(sfConfig::get('app_storage_pool_title')) ?></em>.</p>

<a href="#help-storage-pool"><div>Início</div></a>
<hr/>

</div>

