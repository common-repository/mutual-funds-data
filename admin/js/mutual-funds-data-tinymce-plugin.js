(function(){
  var search_mf_base_url = document.location.protocol + '//' + document.location.host + '/wp-admin/admin-ajax.php?action=mfd_search_by_name&q=';  

  var dialoghtml =         
        '<div class="search-mf-container">'+
            '<div class="mfd-title">' +
                'Table title:&nbsp;&nbsp;'+
                '<input maxlength="100" size="50" placeholder="&nbsp;Enter minimum 5 characters..." min="5" type="text" name="mfd-table-title" id="mfd-table-title">' +
            '</div>'+
            '<div id="mfd-message-container" class="mfd-message"></div>'+
            '<div id="mfd-selection-container" class="mfd-selected"></div>'+
            '<div id="mfd-search-row-1">'+
                '<table>'+
                    '<tr>'+
                        '<td>Search Keyword:&nbsp;&nbsp;</td>'+
                        '<td><input maxlength="50" size="30" placeholder="&nbsp;Enter minimum 3 characters..." min="3" type="text" name="search-keyword" id="search-keyword"></td>'+
                    '</tr>'+
                '</table>'+
            '</div>'+            
            '<div id="mfd-search-row-2" class="mfd-search-results">'+                
            '</div>'+
        '</div>'; 
  
  var buildResult = function(json_data){
        var template_html = '';

        $.each(json_data['mf_data'], function( index, value ) {
            template_html += '<input class="mfd-search-result-items" type="checkbox" name="'+value['scheme_name']+'_name'+'" value="'+value['scheme_code']+'">&nbsp;&nbsp;'+value['scheme_name']+'<br>';
        });            

        return template_html;
  }   

  tinymce.create('tinymce.plugins.GrowwMFD', {
        init: function(ed, url){                
                ed.addButton('groww-mfd-btn', {
                    title: 'Mutual Funds Data',
                    cmd: 'growwmfdBtnCmd',
                    image: url + '/img/mfd-tinymce-btn.png'
                });

                ed.addCommand('growwmfdBtnCmd', function(){
                    var win = ed.windowManager.open({
                        title: 'Search Mutual Funds',
                        body: [                            
                            {
                                type: 'container',
                                name: 'container',
                                label: '',
                                html: dialoghtml
                            }],
                        buttons: [
                            {
                                text: "Search",
                                subtype: "primary",
                                tooltip: "Search",
                                onclick: function() {
                                    $ = ed.getWin().parent.jQuery;
                                    $('#mfd-message-container').hide();
                                    $('#mfd-search-row-2').show();
                                    $('#mfd-search-row-2').html('<div id="loading-img">Loading....</div>');
                                    var query_string = $('#search-keyword').val();
                                    if(query_string.length < 3){
                                        $('#mfd-message-container').show();
                                        $('#mfd-message-container').html('Please enter at least 3 characters in the search box.');
                                        return false;
                                    }
                                    var search_mf_url = search_mf_base_url + encodeURIComponent(query_string);                                    
                                    $.ajax({
                                                'method':'get',
                                                'url':search_mf_url,                                                
                                                'cache':false,
                                                'success': function( data, textStatus ) {                                                    
                                                    data = jQuery.parseJSON(data);                                                    
                                                    if( data.status == '1' ) {          
                                                        if(data['mf_data'].length < 1 ){
                                                            $('#mfd-message-container').show();
                                                            $('#mfd-message-container').html('No Results found. Please enter a different search keyword.');
                                                        }                                              
                                                        var result_html = buildResult(data);                                                        
                                                        $('#mfd-search-row-2').html(result_html);
                                                    } else {
                                                        // error: build our error message text                                                        
                                                        var msg = data.message + '\r' + data.error + '\r';
                                                        // loop over the errors
                                                        $.each(data.errors,function(key,value){
                                                            // append each error on a new line
                                                            msg += '\r';
                                                            msg += '- '+ value;
                                                        });
                                                        // notify the user of the error                                                        
                                                        $('#mfd-search-row-2').hide();
                                                        $('#mfd-message-container').show();
                                                        $('#mfd-message-container').html('Something went wrong.' + msg);
                                                    }
                                                },
                                                'error': function( jqXHR, textStatus, errorThrown ) {
                                                    $('#mfd-search-row-2').hide();
                                                    $('#mfd-message-container').show();
                                                    $('#mfd-message-container').html('Something went wrong.' + msg);
                                                }                                                    
                                        });
                                }                                                            
                            },
                            {
                                text: "Add to Selection",
                                subtype: "primary",
                                tooltip: "Add Selected Search Results for Insertion.",
                                onclick: function() {                    
                                    var selectionHTML = '';
                                    $('#mfd-search-row-2').children().each(function(index){
                                        if($(this).is(':checked')){
                                            selectionHTML += '<input class="mfd-search-result-items" type="checkbox" name="'+$(this).attr('name')+'" value="'+$(this).attr('value')+'" checked>&nbsp;&nbsp;'+$(this).attr('name')+'<br>';;
                                        }                                        
                                    });                                    
                                    $('#mfd-selection-container').append(selectionHTML);
                                }
                            },
                            {
                                text: "Insert",
                                subtype: "primary",
                                tooltip: "Insert the shortcode.",
                                onclick: function() {
                                    win.submit();
                                }
                            },
                            {
                                text: "Cancel",
                                onclick: function() {
                                    win.close();
                                }
                            }],
                        onsubmit: function(e){
                            var selectedSchemeCodes = ''
                            $('#mfd-message-container').hide();
                            $('#mfd-selection-container input').each(function(index){
                                if($(this).is(':checked')){
                                    selectedSchemeCodes += ":" + $( this ).attr('value');                                            
                                }
                            });

                            if(selectedSchemeCodes.length>0){
                                selectedSchemeCodes = selectedSchemeCodes.substring(1, selectedSchemeCodes.length);
                            }else{                                
                                $('#mfd-message-container').show();
                                $('#mfd-message-container').html('Please select at least one Mutual Fund from the list of search results.');
                                return false;
                            }
                            
                            var table_title = $('#mfd-table-title').val();
                            if(table_title.length < 3){
                                $('#mfd-message-container').show();
                                $('#mfd-message-container').html('Please enter a longer Table Title.');
                                return false;
                            }
                            var returnText = '[mfd title="' + table_title + '" schemecodes="'+selectedSchemeCodes+'"]';
                            
                            ed.execCommand('mceInsertContent', 0, returnText);
                        }
                    });
                });
        },
        getInfo: function() {
            return {
                longname: 'Groww Mutual Fund Data Button',
                author: 'Anshul Khare',
                authorurl: 'https://www.linkedin.com/in/anshulkhare/',
                version: "0.1"
            };
        }    
  });
  tinymce.PluginManager.add( 'groww-mfd-tinymceplugin', tinymce.plugins.GrowwMFD );
})();