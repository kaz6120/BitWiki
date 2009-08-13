/**
 * Insert text
 */
/* --- BASE FUNCTION ---- */
function insertAtCursor(comment, myValue) {
    //IE support
    if (document.selection) {
        comment.focus();
        sel = document.selection.createRange();
        sel.text = myValue;
    } else if(comment.selectionStart || comment.selectionStart == '0') { //MOZILLA/NETSCAPE support
        var startPos = comment.selectionStart;
        var endPos   = comment.selectionEnd;
        comment.value = comment.value.substring(0, startPos) 
                      + myValue 
                      + comment.value.substring(endPos, comment.value.length);
    } else {
        comment.value += myValue;
    }
}


/** 
 * Insert Smiley Icon Code
 *
 * @author kaz
 */ 
function smiley(icon) {
    comment = document.getElementById("comment");
    icon = ' ' + icon + ' ';
    insertAtCursor(comment, icon);
    return false;
}

function addCategory(myValue) {
    category = document.getElementById("category");
    category.value += myValue;
}
