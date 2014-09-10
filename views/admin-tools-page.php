<?php

/**
 * Main Admin screen view
 * 
 * @author Mat Lipe
 * 
 * @uses may be overridden in your theme by putting a copy of this file inside a go-live-update-urls folder
 */
?>

<div id="gluu" class="wrap">
    <?php screen_icon('options-general'); ?>
    <h2>Go Live Update Urls</h2>

    <h4> This will replace all occurrences "in the entire database" of the old URL with the New URL.
    <br />
    Uncheck any tables that you would not like to update. </h4>
    <div class="updated fade"><h4> Please Uncheck any Tables which may contain seralized data. The only tables which are currently seralized data safe when using this plugin is <?php echo implode(', ', array_keys( $this->getSerializedTables() )) ; ?>.</h4></div>
    <strong><em>Like any other database updating tool, you should always perfrom a backup before running.</em></strong>
    <p>
        <input type="button" class="button secondary" value="uncheck all" id="uncheck-button"/>
    </p>
    <form method="post" id="gluu-checkbox-form">
        <?php //Make the boxes to select tables
            echo $this->makeCheckBoxes();
        ?>
        <table class="form-table">
            <tr>
                <th scope="row" style="width:150px;"><b>Old URL</b></th>
                <td>
                <input name="oldurl" type="text" id="oldurl" value="" style="width:300px;" />
                </td>
            </tr>
            <tr>
                <th scope="row" style="width:150px;"><b>New URL</b></th>
                <td>
                <input name="newurl" type="text" id="newurl" value="" style="width:300px;" />
                </td>
            </tr>
        </table>
        <p class="submit">
            <?php submit_button('Make it Happen', 'primary', 'gluu-submit'); ?>
        </p>
        <?php 
            echo $nonce;
        ?>

    </form>
</div>
<script type="text/javascript">
    jQuery('#uncheck-button').click( function(){
       if( jQuery(this).val() == 'uncheck all' ){
           jQuery('#gluu-checkbox-form input[type="checkbox"]').attr('checked',false);
           jQuery(this).val('check all');
       } else {
           jQuery('#gluu-checkbox-form input[type="checkbox"]').attr('checked',true);
           jQuery(this).val('uncheck all');
       }
    });
</script>