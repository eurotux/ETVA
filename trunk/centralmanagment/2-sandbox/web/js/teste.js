Ext.Direct.addProvider(Ext.app.EXTDIRECT_API);

server.multiply({"number":4},function(provider, response) {
alert(response['result']);
         // process response

});


//console.log(server.multiply);