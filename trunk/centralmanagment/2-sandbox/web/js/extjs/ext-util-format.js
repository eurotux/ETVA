/**
 * Overridden Ext.util.Format.fileSize for bigger sizes like GB and TB and support localized formats
 * 
 * Simple format for a file size (xxx bytes, xxx KB, xxx MB, xxx GB, xxx TB)
 * 
 * @param {Number/String}
 *            size The numeric value to format
 * @return {String} The formatted file size
 */
if(Ext.util.Format){
    Ext.util.Format.fileSize = function(size, formatString) {
        var numberFormatString = formatString || '0.,00/i';
        if (size < 1024) {
            return Ext.util.Format.number(size, numberFormatString) + " B";
        } else if (size < 1048576) {
            return Ext.util.Format.number(Math.round(((size * 100) / 1024)) / 100, numberFormatString) + " KB";
        } else if (size < 1073741824) {
            return Ext.util.Format.number(Math.round(((size * 100) / 1048576)) / 100, numberFormatString) + " MB";
        } else if (size < 1099511627776) {
            return Ext.util.Format.number(Math.round(((size * 100) / 1073741824)) / 100, numberFormatString) + " GB";
        } else {
            return Ext.util.Format.number(Math.round(((size * 100) / 1099511627776)) / 100, numberFormatString) + " TB";
        }
    };
}
