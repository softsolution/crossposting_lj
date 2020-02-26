$(document).ready(function(){
    checkGroupList();
});
function checkGroupList(){
if ($('input[name="crosspost"]:checked').val() == 0){
		$('select#showin').attr('disabled', 'disabled');
	} else {
		$('select#showin').attr('disabled', '');
	}

}