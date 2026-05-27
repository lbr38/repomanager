/**
 *  Toggle environment proctection
 */
$(document).on('click','img.env-protection', function () {
    const img = $(this);
    const protected = img.attr('protected');
    const id = img.attr('env-id');
    const name = img.attr('env-name');
    
    if (protected == 'false') {
        img.attr('src','/assets/icons/locked-red.svg');
        img.attr('title','Unprotect environment');
        img.attr('protected','true');
        img.addClass('icon');
        img.removeClass('icon-lowopacity');
    } else {
        img.attr('src','/assets/icons/unlocked.svg');
        img.attr('title','Protect environment');
        img.addClass('icon-lowopacity');
        img.attr('protected','false');
        img.removeClass('icon');
    }
});

/**
 *  Event: add a new environment by pressing enter
 */
$(document).on('keypress','input[name="add-env-name"]', function (e) {
    const keycode = (event.keyCode ? event.keyCode : event.which);

    if (keycode == '13') {
        e.stopPropagation();
        myenvironment.add();
    }
});

/**
 *  Event: add a new environment by clicking the add button
 */
$(document).on('click','#add-env-btn',function () {
    myenvironment.add();
});

/**
 *  Event: edit environments by pressing enter
 */
$(document).on('keypress','input[name="env-name"]', function (e) {
    const keycode = (event.keyCode ? event.keyCode : event.which);

    if (keycode == '13') {
        e.stopPropagation();
        myenvironment.edit();
    }
});

/**
 *  Event: edit environments by clicking the edit button
 */
$(document).on('click','#edit-env-btn', function () {
    myenvironment.edit();
});

/**
 *  Event: delete an environment
 */
$(document).on('click','.delete-env-btn',function () {
    const id = $(this).attr('env-id');
    const name = $(this).attr('env-name');

    myconfirmbox.print(
        {
            'title': 'Delete environment',
            'message': 'Are you sure you want to delete environment <b>' + name + '</b>?',
            'buttons': [
            {
                'text': 'Delete',
                'color': 'red',
                'callback': function () {
                    myenvironment.delete(id);
                }
            }]
        }
    );
});
