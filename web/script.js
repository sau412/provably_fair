// Show only one block from the page
function show_block(block_name) {
        $("#main_block").load("./?ajax=1&block=" + encodeURI(block_name));
        return true;
}
