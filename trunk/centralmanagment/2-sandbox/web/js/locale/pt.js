if(Ext.ux.Andrie.pPageSize){
   Ext.apply(Ext.ux.Andrie.pPageSize.prototype, {
      beforeText: 'Visualizar',
      afterText: 'registos'
   });
}


if(Ext.ux.Wiz){
   Ext.apply(Ext.ux.Wiz.prototype, {
      previousButtonText : '&lt; Anterior',
      nextButtonText : 'Seguinte &gt;',
      cancelButtonText : 'Cancelar',
      finishButtonText : 'Finalizar'      
   });
}

if(Ext.ux.Wiz.Header){
   Ext.apply(Ext.ux.Wiz.Header.prototype, {
      stepText : 'Passo {0} de {1}: {2}'
   });
}


if(Ext.ux.Wiz.West){
   Ext.apply(Ext.ux.Wiz.West.prototype, {
      title : 'Passos'
   });
}

if(Ext.form.VTypes['pool_validText']){   
   Ext.form.VTypes['pool_validText'] = 'Valor máximo permitido é 999!';
}


if(Ext.ux.grid.TotalCountBar){
   Ext.apply(Ext.ux.grid.TotalCountBar.prototype, {
      displayMsg : 'Total de {2} registo(s)',
      emptyMsg : 'Sem registos',
      refreshText : 'Atualizar'
   });
}


var i18n = {
    'Add...'                    : 'Adicionar...',
    'Help'                      : 'Ajuda',
    'Invalid'                   : 'Inválido',
    'Select...'                 : 'Seleccione...',
    'One day'                   : 'Um dia',
    'Two days'                  : 'Dois dias',
    'Five days'                 : 'Cinco dias',
    'One week'                  : 'Uma semana',
    'Two weeks'                 : 'Duas semanas',
    'Date filter'               : 'Filtrar data',
    'From'                      : 'De',
    'To'                        : 'Até',
    'Presets'                   : 'Intervalos',
    'Last day'                  : 'Últimas 24 horas',
    'Last hour'                 : 'Última hora',
    'Last 2 hour'               : 'Últimas 2 horas',
    'Last week'                 : 'Última semana',
    'Clear'                     : 'Limpar',
    'Selected'                  : 'Seleccionado',
    'Available'                 : 'Disponivel',
    'Refresh'                   : 'Atualizar',
    'Java required!'            : 'Necessita Java!',
    'Active'                    : 'Activo',
    'Yes'                       : 'Sim',
    'No'                        : 'Não',
    'Empty!'                    : 'Vazio!',
    'All'                       : 'Todas',
    'Items'                     : 'Itens',
    'Name'                      : 'Nome',
    'Create'                    : 'Criar',
    'Name...'                   : 'Nome...',
    'Cancel'                    : 'Cancelar',
    'Close'                     : 'Fechar',
    'Drag and Drop to reorder'  : 'Arrastar e largar para re-ordenar',
    'Save'                      : 'Guardar',
    'Change'                    : 'Alterar',
    'Update'                    : 'Atualizar',
    'Move up'                   : 'Mover para cima',
    'Move down'                 : 'Mover para baixo',
    'Users'                     : 'Utilizadores',
    'User'                      : 'Utilizador',
    'Disk usage'                : 'Utilização disco',
    'Daily'                     : 'Diário',
    'Weekly'                    : 'Semanal',
    'Monthly'                   : 'Mensal',
    'Date'                      : 'Data',
    'Last Execution'            : 'Últ. Execução',
    'Next Execution'            : 'Próx. Execução',
    'Schedule'                  : 'Horário',
    'Databases'                 : 'Bases dados',
    'Verify'                    : 'Verificar',
    'Overwrite'                 : 'Sobrescrever',
    'Incremental'               : 'Incremental',
    'Free'                      : 'Livre',
    'Used'                      : 'Utilizado',
    'Primavera is running'      : 'Primavera a correr',
    'SQL Server is running'     : 'SQL Server a correr',
    'IP address'                : 'Endereço IP',
    'Netmask'                   : 'Máscara de rede',
    'Full backup'               : 'Backup integral',
    'Full restore'              : 'Restauro integral'
};
