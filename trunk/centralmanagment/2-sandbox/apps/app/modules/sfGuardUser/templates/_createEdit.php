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
 * This JS is mainly used to handle action in the list
 * health to create.
 *
 * @author Fenqiang Zhuang
 * @Date March 8, 2007
 */
feyaSoft.home.extjsTutor.gridForm.CreateEdit = function() {

	Ext.QuickTips.init();
   	Ext.form.Field.prototype.msgTarget = 'side';

    this.id = new Ext.form.Hidden({
        id: 'form-account-id',
        value: null,
        name: 'Id'
    });
    this.firstname = new Ext.form.TextField({
        fieldLabel: 'First name',
        allowBlank: false,
        name: 'FirstName',
        maxLength: 10,
	    anchor: '90%'
    });
    this.lastname = new Ext.form.TextField({
        fieldLabel: 'Last Name',
        allowBlank: false,
        name: 'LastName',
        maxLength: 50,
	    anchor: '90%'
    });
    this.username = new Ext.form.TextField({
        fieldLabel: 'Username',
        name: 'Username',
        maxLength: 50,
        allowBlank: false,
	    anchor: '90%'
    });
    this.email = new Ext.form.TextField({
        fieldLabel: 'Email',
        name: 'Email',
        vtype:'email',
        blankText: 'Please provides your correct email address',
        maxLength: 50,
	    anchor: '90%'
    });
    this.active = new Ext.ux.ComboBox({
    	selectOnFocus:true,
        editable: false,
        width: 100,
        mode: 'local',
        triggerAction: 'all',
        name:'IsActive',
    	fieldLabel: 'Active',
 	    xtype:'combo',
 	    emptyText:'Select...',
 	    allowBlank: false,
 	    store:['true','false']
    });
    // pre-define fields in the form
	this.note = new Ext.form.TextArea({
	    fieldLabel: 'Note',
	    name: 'note',
	    height: 80,
	    anchor: '90%'
	});
	this.password = new Ext.form.TextField({
	    id: 'form-password',
        fieldLabel: 'Password',
        allowBlank: false,
        inputType: 'password',
        name: 'password',
        minLength: 4,
        anchor: '90%'
    });
    this.confirmPassword = new Ext.form.TextField({
        id: 'form-confirmPassword',
        fieldLabel: 'Confirm Password',
        allowBlank: false,
        inputType: 'password',
        name: 'confirmpassword',
        minLength: 4,
        anchor: '90%'
    });

    // field set
    var allFields = new Ext.form.FieldSet({
        title: 'Account Details',
        autoHeight:true,
        items: [this.id, this.firstname, this.lastname, this.username, this.password, this.confirmPassword, this.email, this.active, this.note]
    });

    // define window and pop-up - render formPanel
    feyaSoft.home.extjsTutor.gridForm.CreateEdit.superclass.constructor.call(this, {
        id: 'create-edit-account-form',
        baseCls: 'x-plain',
        labelWidth: 90,
        defaultType: 'textfield',
        buttonAlign:'center',
        reader: new Ext.data.JsonReader({root: 'data'},['Id','FirstName','LastName', 'Username', 'Email','note', 'IsAsctive']),
        items: [allFields],

        buttons: [{
            id: 'form-create-update-button',
            text: 'Save',
            handler: function() {
                if (Ext.getCmp('create-edit-account-form').form.isValid()) {
            	    if (Ext.getCmp('form-password').getValue() != Ext.getCmp('form-confirmPassword').getValue()) {
                		Ext.MessageBox.alert('Error Message', 'Password not match, please reenter again');
                	} else {
                	    var hiddenId = Ext.getCmp('form-account-id').getValue();
                        alert('hiddenId'+hiddenId);
                	    this.url = 'tutorAccount/create';
                	    if ( hiddenId != null &&  hiddenId != '' && Number(hiddenId) > 0) {
                	        this.url = 'tutorAccount/update';
                	    }
		 		        Ext.getCmp('create-edit-account-form').form.submit({
		 		            url: this.url,
				            waitMsg:'In processing',
				            failure: function(form, action) {
							    Ext.MessageBox.alert('Error Message', action.result.errorInfo);
							},
							// everything ok...
							success: function(form, action) {
							    Ext.Message.msgStay('Confirm', action.result.info, 2000);
							    Ext.getCmp('list-account-form-panel').reload();
							}
				        });
                	}
                } else{
					Ext.MessageBox.alert('error', 'Please fix the errors noted.');
				}
	        }
        }]
    });

};

Ext.extend(feyaSoft.home.extjsTutor.gridForm.CreateEdit, Ext.form.FormPanel, {

    // load data
    load : function(id) {
        // this.clean();

        this.form.load({url:'sfGuardUser/jsonGridInfo',
                         params:{id : id},
                         waitMsg:'Loading'}, {delay: 750});
        // set username disable
        this.username.setDisabled(true);
        Ext.getCmp('form-create-update-button').setText('Update');


    },

    create : function() {
        this.username.setDisabled(false);
        Ext.getCmp('form-create-update-button').setText('Save');
        this.clean();
    },

    clean : function() {
        Ext.getCmp('form-account-id').setValue(null);
        this.firstname.setValue(null);
        this.lastname.setValue(null);
        this.username.setValue(null);
        this.email.setValue(null);
        this.active.setValue(null);
        this.note.setValue(null);
        this.password.setValue(null);
        this.confirmPassword.setValue(null);
    }

});

</script>