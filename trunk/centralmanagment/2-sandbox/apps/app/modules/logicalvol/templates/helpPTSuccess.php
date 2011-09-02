<a id="help-lvol"><h1>Logical Volumes</h1></a>
<p>As operações disponíveis sobre os <em>logical volumes</em> são as seguintes:</p>

<ul>
    <li><a href="#help-lvol-add">Criar</a></li>
    <li><a href="#help-lvol-rs">Redimensionar</a></li>
    <li><a href="#help-lvol-rm">Remover</a></li>
</ul>

<br/>
<hr/>


<a id="help-lvol-add"><h1>Criar <em><b>Volume Group</b></em></h1></a>
<p>Para criar um <em>logical volume</em> acede-se ao sub-menu de contexto sobre um qualquer <em>logical volume</em> e
selecciona-se <em>Adicionar logical volume</em>.</p>
<p>Na janela de criação deverá ser introduzido o nome pretendido,
o <em>volume group</em> a partir do qual se criará, e o tamanho que não
deverá exceder o tamanho disponível no <em>volume group</em>.
</p>
<p>
Um <em>physical volume</em> está disponível quando não está alocado a nenhum <em>volume group</em> e
encontra-se inicializado.
</p>
<a href="#help-lvol"><div>Início</div></a>
<hr/>

<a id="help-lvol-rs"><h1>Redimensionar <em><b>Logical Volumes</b></em></h1></a>
No redimensionamento selecciona-se o <em>logical volume</em> que se pretende redimensionar e
acede-se ao sub-menu de contexto. Aí existe a opção <em>Redimensionar logical volume</em> que
permite aumentar/reduzir o tamanho do <em>logical volume</em>.

<p><b>Nota:</b> Ao reduzir o tamanho do <em>logical volume</em> poderá tornar os dados existentes inutilizados.
    É da responsabilidade do utilizador verificar se é comportável/seguro o
redimensionamento do <em>logical volume</em> sem afectar os dados nele contidos.</p>
<a href="#help-lvol"><div>Início</div></a>
<hr/>


<a id="help-lvol-rm"><h1>Remover <em><b>Logical Volumes</b></em></h1></a>
<p>Na remoção de um <em>logical volume</em>, no sub-menu de contexto existe a opção <em>Remover logical
volume</em>. O <em>logical volume</em> só será removido se não tiver associado a nenhuma máquina
virtual. Para verificar se está em uso passa-se o rato por cima do <em>logical volume</em> e observar
a informação contida no <em>tooltip</em> que aparece.</p>
<a href="#help-lvol"><div>Início</div></a>
<hr/>

