// JS code here


$( document ).ready(function() {
       $( "#sortable" ).sortable();

	$("#sortable").sortable({
    	 stop: function(event, ui) {
        var data = "";
	 data = $( "#sortable" ).sortable( "toArray", {attribute: 'name'} );
        $("form > [name='new_order']").val(data);
    	 }
	});
       
});

