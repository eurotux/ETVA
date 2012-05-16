<style type="text/css">
#the-table { border:1px solid #bbb;border-collapse:collapse; }
#the-table td,#the-table th { border:1px solid #ccc;border-collapse:collapse;padding:5px; }
</style>
<script>
Ext.onReady(function() {
    var btn = Ext.get("create-grid");
  btn.on("click", function(){
    btn.dom.disabled = true;

    // create the grid
    var grid = new Ext.grid.TableGrid("the-table", {
      stripeRows: true // stripe alternate rows
    });
    grid.render();
  }, false, {single:true}); // run once
});



/**
 * @class Ext.grid.TableGrid
 * @extends Ext.grid.Grid
 * A Grid which creates itself from an existing HTML table element.
 * @constructor
 * @param {String/HTMLElement/Ext.Element} table The table element from which this grid will be created -
 * The table MUST have some type of size defined for the grid to fill. The container will be
 * automatically set to position relative if it isn't already.
 * @param {Object} config A config object that sets properties on this grid and has two additional (optional)
 * properties: fields and columns which allow for customizing data fields and columns for this grid.
 * @history
 * 2007-03-01 Original version by Nige "Animal" White
 * 2007-03-10 jvs Slightly refactored to reuse existing classes
 */
Ext.grid.TableGrid = function(table, config) {
  config = config || {};
  Ext.apply(this, config);
  var cf = config.fields || [], ch = config.columns || [];
  table = Ext.get(table);

  var ct = table.insertSibling();

  var fields = [], cols = [];
  var headers = table.query("thead th");
  for (var i = 0, h; h = headers[i]; i++) {
    var text = h.innerHTML;
    var name = 'tcol-'+i;

    fields.push(Ext.applyIf(cf[i] || {}, {
      name: name,
      mapping: 'td:nth('+(i+1)+')/@innerHTML'
    }));

    cols.push(Ext.applyIf(ch[i] || {}, {
      'header': text,
      'dataIndex': name,
      'width': h.offsetWidth,
      'tooltip': h.title,
      'sortable': true
    }));
  }

  var ds  = new Ext.data.Store({
    reader: new Ext.data.XmlReader({
      record:'tbody tr'
    }, fields)
  });

  ds.loadData(table.dom);

  var cm = new Ext.grid.ColumnModel(cols);

  if (config.width || config.height) {
    ct.setSize(config.width || 'auto', config.height || 'auto');
  } else {
    ct.setWidth(table.getWidth());
  }

  if (config.remove !== false) {
    table.remove();
  }

  Ext.applyIf(this, {
    'ds': ds,
    'cm': cm,
    'sm': new Ext.grid.RowSelectionModel(),
    autoHeight: true,
    autoWidth: false
  });
  Ext.grid.TableGrid.superclass.constructor.call(this, ct, {});
};

Ext.extend(Ext.grid.TableGrid, Ext.grid.GridPanel);




</script>
<div class="sf_admin_list">
  <?php if (!$pager->getNbResults()): ?>
    <p><?php echo __('No result', array(), 'sf_admin') ?></p>
  <?php else: ?>
    <table id="the-table" cellspacing="0">
      <thead>
        <tr>          
          <th>Id</th>
           <th>Name</th>
<th>Description
</th>
 <th id="sf_admin_list_th_actions"><?php echo __('Actions', array(), 'sf_admin') ?></th>
        </tr>
      </thead>
      <tfoot>
        <tr>
          <th colspan="5">
            <?php if ($pager->haveToPaginate()): ?>
              <?php include_partial('sfGuardGroup/pagination', array('pager' => $pager)) ?>
            <?php endif; ?>

            <?php echo format_number_choice('[0] no result|[1] 1 result|(1,+Inf] %1% results', array('%1%' => $pager->getNbResults()), $pager->getNbResults(), 'sf_admin') ?>
            <?php if ($pager->haveToPaginate()): ?>
              <?php echo __('(page %%page%%/%%nb_pages%%)', array('%%page%%' => $pager->getPage(), '%%nb_pages%%' => $pager->getLastPage()), 'sf_admin') ?>
            <?php endif; ?>
          </th>
        </tr>
      </tfoot>
      <tbody>
        <?php foreach ($pager->getResults() as $i => $sf_guard_group): $odd = fmod(++$i, 2) ? 'odd' : 'even' ?>
          <tr class="sf_admin_row <?php echo $odd ?>">            
            <?php include_partial('sfGuardGroup/list_td_tabular', array('sf_guard_group' => $sf_guard_group)) ?>
            <?php include_partial('sfGuardGroup/list_td_actions', array('sf_guard_group' => $sf_guard_group, 'helper' => $helper)) ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <button id="create-grid" type="button">Create grid</button>
  <?php endif; ?>
</div>
<script type="text/javascript">
/* <![CDATA[ */
function checkAll()
{
  var boxes = document.getElementsByTagName('input'); for(index in boxes) { box = boxes[index]; if (box.type == 'checkbox' && box.className == 'sf_admin_batch_checkbox') box.checked = document.getElementById('sf_admin_list_batch_checkbox').checked } return true;
}
/* ]]> */
</script>
