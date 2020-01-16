// Show only one block from the page
function show_block(block_name) {
        $("#main_block").load("./?ajax=1&block=" + encodeURI(block_name));
        return true;
}

// Show first block and hide second
function show_and_hide(block_to_show, block_to_hide) {
	if(document.getElementById(block_to_show)) {
		document.getElementById(block_to_show).style.display='block';
	}
	if(document.getElementById(block_to_hide)) {
		document.getElementById(block_to_hide).style.display='none';
	}
	return false;
}
