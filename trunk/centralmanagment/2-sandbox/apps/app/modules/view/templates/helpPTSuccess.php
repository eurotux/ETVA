<div class="help">
<div class="help-section">
    <a id="help-firttimeW-main"><h1>Assistente de Configuração Inicial</h1></a>
    <p>O assistente de configuração inicial, guia-o através dos seguintes passos:</p>

    <ul>
        <li><a href="#help-firttimeW-password">Alteração da <em>password</em> inicial</a></li>
        <li><a href="#help-firttimeW-mac">Geração da <em>Mac pool</em></a></li>
        <li><a href="#help-firttimeW-net">Gestão de Preferências</a></li>
    </ul>
    <br/>
    <hr/>

    <a id="help-firttimeW-password"><h2>Alteração da <em><b>password</b></em> inicial</h2></a>
    <p>Por questões de segurança, na primeira utilização a <em>password</em> deve ser alterada. Preencha os campos e seleccione a <em>Alterar Password</em>.</p>
    <p>Note que o botão <em>seguinte</em> apenas fica disponível após a alteração da <em>password</em>.
    </p>
    <a href="#help-firttimeW-main"><div>Início</div></a>
    <hr/>

    <a id="help-firttimeW-mac"><h2>Geração da <em><b>Mac pool</b></em></h2></a>
    <p>Neste passo é gerada uma <em>pool</em> de endereços MAC, a atribuir às interfaces de rede das máquinas da infra-estrutura.
    Utilize o valor aproximado, uma vez que é possível acrescentar endereços posteriormente.</p>
    <a href="#help-firttimeW-main"><div>Início</div></a>
    <hr/>

    <a id="help-firttimeW-net"><h2>Preferências</h2></a>
    <p>No passo <em>Preferências</em> são apresentadas opções para a gestão de endereços de rede, de ligação remota, e de registo de eventos.</p>
    <p>Para alterar estas opções seleccione <em>Gerir Preferências</em>.</p>
    <a href="#help-firttimeW-main"><div>Início</div></a>
    <hr/>
</div>

<div class="help-section">
    <a id="help-left-panel-main"><h1><em><b>Nodes</b></em> (painel esquerdo)</h1></a>

    <p>O painel <em>Nodes</em> pertence à tela principal do <em>Central Management</em> e lista as máquinas reais/servidores de virtualização
    (as máquinas associadas a cada <em>nó</em>).</p>

    <p>O primeiro <em>nó</em> visível é o <b><em>Main</em></b>, sob o qual se encontram os vários servidores de virtualização registados no <em>CM</em>.</p>

    <p>Ao seleccionar um <em>nó</em> (servidor de virtualização) é carregada a informação correspondente no painel principal (painel da direita).</p>

    <p>Ao clicar com o botão direito do rato sobre o <em>nó</em> acede-se ao menu de opções, onde é possível seleccionar efectuar as seguintes operações:</p>

    <ul>
        <li>Inicialização</li>
        <ul>
            <li><a href="#help-left-panel-authorize">Autorizar <em>nó</em></a></li>
            <li><a href="#help-left-panel-restart">Re-inicializar <em>nó</em></a></li>
        </ul>
        <li>Node</li>
        <ul>
            <li><a href="#help-left-panel-loadnode">Carregar <em>nó</em></a></li>
            <li><a href="#help-left-panel-edit">Editar <em>nó</em></a></li>
            <li><a href="#help-left-panel-connectivity">Opções de conectividade</a></li>
            <li><a href="#help-left-panel-keymap">Alterar <em>keymap</em></a></li>
            <li><a href="#help-left-panel-status">Verificar o estado do <em>nó</em></a></li>
            <li><a href="#help-left-panel-maintenance">Manutenção/Recuperar</a></li>
            <li><a href="#help-left-panel-shutdown">Desligar</a></li>
            <li><a href="#help-left-panel-remove">Remover <em>nó</em></a></li>
            <li><a href="#help-left-panel-addperm">Atribuir permissões</a></li>
        </ul>
        <li>Servidor</li>
        <ul>
            <li><a href="#help-left-panel-keymap">Alterar keymap</a></li>
            <li><a href="#help-left-panel-addperm">Atribuir permissões</a></li>
            <li><a href="#help-left-panel-start_stop">Iniciar/Parar</a></li>
        </ul>
    </ul>
    <p><b>Nota: </b><!--A lista de operações correspondentes aos pontos <em>Inicializar</em> e <em>Node</em>, são referentes ao <em>Node</em> onde foi solicitado o menu.-->
    Caso tenha sido seleccionado um servidor ao invés de um <em>nó</em>, todas as operações são referentes ao <b><em>nó</em> pai</b>, com excepção da opção <em>Alterar keymap</em>.
    </p>
    <br/>
    <hr/>

    <a id="help-left-panel-authorize"><h1>Autorizar <em><b>nó</b></em></h1></a>
    <p>Quando um <em>nó</em>, com o agente de virtualização instalado, é adicionado à rede, o <em>Cental Management</em> é notificado da sua existência.
    Caso a afirmação anterior se verifique, o <em>nó</em> aparece listado na árvore de nós.</p>
    <p>Para tornar possível a gestão do <em>nó</em> é necessário seleccionar a opção <em>Autorizar nó</em>.</p>
    <a href="#help-left-panel-main"><div>Início</div></a>
    <hr/>

    <a id="help-left-panel-restart"><h1>Re-inicializar <em><b>nó</b></em></h1></a>
    <p>A opção <em>Re-inicializar nó</em> reinicia ao agente de virtualização do nó (não reinicia o seu sistema operativo).
    <p><b>Nota: </b>Nos servidores existentes sob o <em>nó</em>, não há qualquer tipo de modificação.</p>
    <a href="#help-left-panel-main"><div>Início</div></a>
    <hr/>

    <a id="help-left-panel-loadnode"><h1>Carregar <em><b>nó</b></em></h1></a>
    <p>Ao seleccionar esta opção é enviado um pedido ao <em>Central Management</em> para que o estado do <em>nó</em> em questão seja actualizado.</p>
    <a href="#help-left-panel-main"><div>Início</div></a>
    <hr/>

    <a id="help-left-panel-edit"><h1>Editar nó</h1></a>
    <p>A opção <em>Editar nó</em> possibilita a edição de algumas propriedades do servidor de virtualização, nomeadamente, nome da máquina e configuração de <em>fencing</em>.</p>
    <a href="#help-left-panel-main"><div>Início</div></a>
    <hr/>

    <a id="help-left-panel-connectivity"><h1>Opções de conectividade</h1></a>
    <p>Em <em>Opções de conectividade</em>, é possível editar a configuração da interface de gestão (<em>Management(</em>) ao
    qual se encontra ligado o agente de virtualização.</p>
    <p>É possível definir o endereço IP e o servidor de DNS.</p>
    <p><b>Nota: </b>Esta opção apenas está disponível na versão <em>Enterprise</em>.</p>
    <a href="#help-left-panel-main"><div>Início</div></a>
    <hr/>

    <a id="help-left-panel-keymap"><h1>Alterar <em><b>keymap</b></em></h1></a>
    <p>Permite alterar a definição do <em>layout</em> do teclado, no <em>nó</em> ou servidor seleccionado.</p>
    <a href="#help-left-panel-main"><div>Início</div></a>
    <hr/>

    <a id="help-left-panel-addperm"><h1>Alterar permissões</h1></a>
    <p>Permite definir o acesso ao nível do servidor ou do datacenter (em nó).</p>    
    <p>Na janela que aparece, selecione os utilizadores/grupos aos quais pretende dar acesso.</p>
    <a href="#help-left-panel-main"><div>Início</div></a>
    <hr/>

    <a id="help-left-panel-start_stop"><h1>Iniciar/Parar</h1></a>
    <p>Permite iniciar/parar servidor.</p>    
    <a href="#help-left-panel-main"><div>Início</div></a>
    <hr/>


    <a id="help-left-panel-status"><h1>Verificar o estado do <em>nó</em></h1></a>
    <p>Força a verificação do estado do <em>nó</em>.</p>
    <a href="#help-left-panel-main"><div>Início</div></a>
    <hr/>

    <a id="help-left-panel-maintenance"><h1>Manutenção/Recuperar</h1></a>
    <p>Coloca o nó em manutenção, migrando os servidores para um nó de <em>Spare</em> caso esteja configurado.
    A opção <em>Recuperar</em> permite fazer uma verificação de sistema do nó e recuperá-lo para o estado activo.</p>
    <a href="#help-left-panel-main"><div>Início</div></a>
    <hr/>

    <a id="help-left-panel-shutdown"><h1>Desligar</h1></a>
    <p>Desliga um nó de virtualização civilizadamente.</p>
    <a href="#help-left-panel-main"><div>Início</div></a>
    <hr/>

    <a id="help-left-panel-remove"><h1>Remover <em><b>nó</b></em></h1></a>
    <p>A opção <em>Remover nó</em> remove um Node. Para concluir a opção basta responder afirmativamente à mensagem de confirmação.</p>
    <p>Esta opção não remove o agente de virtualização</p>

    <p><b>Nota 1: </b>Esta opção apenas remove o servidor de virtualização do <em>Central Management</em>,
    eliminado a informação que este retém sobre ele.</p>
    <p><b>Nota 2: </b>Nenhum volume de armazenamento é apagado.</p>
    <a href="#help-left-panel-main"><div>Início</div></a>
    <hr/>
</div>


<!-- ISO MANAGER -->
<div class="help-section">
    <a id="help-iso-main"><h1>Gestor de ISOs</h1></a>

    <p>O gestor de ISOs é uma ferramenta que permite fazer a gestão das imagens a utilizar na criação de novas máquinas virtuais.
        No momento da criação das máquinas virtuais é possível seleccionar uma das imagens carregadas.</p>

    <p>
    O painel <em>Gestor de ISOs</em> é constituído por uma grelha central, que contém as imagens já carregadas, e pela barra de opções (acima).
    As opções disponíveis são as seguintes:
    </p>

    <ul>
        <li><a href="#help-iso-upload"><em>Applet</em> de <em>Upload</em> (modo FTP)</a></li>
        <li><a href="#help-iso-download"><em>Download</em></a></li>
        <li><a href="#help-iso-rename">Renomear</a></li>
        <li><a href="#help-iso-remove">Apagar</a></li>
    </ul>


    <p><b>Nota: </b>O número de imagens admitido é limitado pelo espaço disponível no <em>Central Management</em>.</p>
    <br/>
    <hr/>

    <a id="help-iso-upload"><h1><em><b>Applet</b></em> de <em><b>Upload</b></em> (modo FTP)</h1></a>
    <p>
    Para efectuar o envio de imagens para o <em>Central Management</em> deve seguir os seguintes passos:
    </p>

    <ol>
        <li>Seleccionar na barra superior, a opção <em>Applet de Upload (modo FTP)</em>;</li>
        <li>No aviso de segurança seleccionar <em>Run</em>;</li>
        <li>Seleccionar a(s) imagem(ns) através da opção <em>Procurar</em>, encontrar os ficheiros, e de seguida carregar em <em>Open</em>;</li>
        <li>Conferir a lista de imagens a enviar (caso pretenda pode utilizar os botões de remoção para retirar ficheiros indesejados);</li>
        <li>Seleccionar a opção enviar (as imagens são copiadas para o <em>Central Management</em>).</li>
    </ol>

    <p><b>Nota 1: </b>Após o envio, as imagens aparecem listadas no painel <em>Gestor de ISOs</em>.</p>
    <p><b>Nota 2: </b>Para executar a <em>applet</em> de envio é necessário ter instalado o <em>Java plug-in</em>.</p>
    <a href="#help-iso-main"><div>Início</div></a>
    <hr/>

    <a id="help-iso-download"><h1><em><b>Download</b></em></h1></a>
    <p>
    A opção de <em>download</em> possibilita descarregar uma das imagens que se encontram no <em>Central Management</em>,
    basta seleccionar a linha correspondente à imagem pretendida e seleccionar a opção de <em>download</em>.
    </p>
    <a href="#help-iso-main"><div>Início</div></a>
    <hr/>

    <a id="help-iso-rename"><h1>Renomear</h1></a>
    <p>
    Para modificar o nome de uma imagem que esteja no <em>Central Management</em>
    selecciona-se a linha correspondente à imagem, e escolhe-se a opção <em>Renomear</em>.
    De seguida surge uma janela onde é possível introduzir o novo nome. Proceder à alteração e <em>Guardar</em>.
    </p>
    <a href="#help-iso-main"><div>Início</div></a>
    <hr/>

    <a id="help-iso-remove"><h1>Apagar</h1></a>
    <p>
    Para apagar uma imagem que esteja no <em>Central Management</em>
    selecciona-se a linha correspondente à imagem, e escolhe-se a opção <em>Apagar</em>.
    Responder afirmativamente à mensagem de confirmação.
    </p>
    <a href="#help-iso-main"><div>Início</div></a>
    <hr/>
</div>

<div class="help-section">
    <a id="help-bottom-panel-main"><h1>Painel de Informação</h1></a>
    <p>O painel de informação pertence à tela principal do <em>Central Management</em> e
    mostra o sucesso dos eventos de sistema.</p>
    <hr/>
</div>
</div>
