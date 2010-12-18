<script>

/**
 * Copyright(c) 2006-2009, FeyaSoft Inc. All right reserved.
 * ====================================================================
 * Licence
 * ====================================================================
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY
 * KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY,FITNESS FOR A PARTICULAR PURPOSE
 * AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
Ext.ns("feyaSoft.home.extjsTutor.gridForm");

/**
 * This JS is mainly used to handle list account. 
 *
 * @author Fenqiang Zhuang
 * @Date July 11, 2008
 */
feyaSoft.home.extjsTutor.gridForm.Main = function() {

   // main panel
    var listGrid = new feyaSoft.home.extjsTutor.gridForm.List();
    
    var createEdit = new feyaSoft.home.extjsTutor.gridForm.CreateEdit();

   /************************************************************
    * Constructor for the Ext.grid.EditorGridPanel
    ************************************************************/ 
    feyaSoft.home.extjsTutor.gridForm.Main.superclass.constructor.call(this, {
    	id: 'account-grid-form',
        frame: true,
        labelAlign: 'left',
        bodyStyle:'padding:5px',
        layout: 'column',	
        items: [{
            columnWidth: 0.6,
            layout: 'fit',
            autoHeight: true,
            items: [listGrid]
        },{
            columnWidth: 0.4,        
            defaults: {width: 320},	// Default config options for child items
            defaultType: 'textfield',
            autoHeight: true,
            bodyStyle: Ext.isIE ? 'padding:0 0 5px 15px;' : 'padding:10px 15px;',
            border: false,
            style: {
                "margin-left": "10px", // when you add custom margin in IE 6...
                "margin-right": Ext.isIE6 ? (Ext.isStrict ? "-10px" : "-13px") : "0"  // you have to adjust for it somewhere else
            }
            ,
            items: [createEdit]
        }]   	
    });	 
}

// define public methods		
Ext.extend(feyaSoft.home.extjsTutor.gridForm.Main, Ext.Panel, { 
});


</script>