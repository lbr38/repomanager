class ConfirmBox
{
    /**
     * Print a confirm box with the specified data
     * @param {*} data
     */
    print(data)
    {
        // Confirm box html
        var confirmBoxHtml = '<div id="confirm-box" class="confirm-box">'

        // Confirm box inner content
        var innerHtml = '<div class="flex flex-direction-column row-gap-10 padding-left-15 padding-right-15">'

        // Container for title and message
        innerHtml += '<div>';

        // If there is a title
        if (data.title != "") {
            innerHtml += '<div class="flex justify-space-between">';
            innerHtml += '<h6 class="margin-top-0 margin-bottom-0 wordbreakall">' + data.title.toUpperCase() + '</h6>';
            innerHtml += '<img src="/assets/icons/close.svg" class="icon-large lowopacity confirm-box-cancel-btn" title="Close" />';
            innerHtml += '</div>';
        }

        // If there is a message
        if (!empty(data.message)) {
            innerHtml += '<p class="note">' + data.message + '</p>';
        }

        // Close container for title and message
        innerHtml += '</div>';

        // Container for buttons
        innerHtml += '<div class="grid grid-2 column-gap-15 row-gap-15">';

        // Loop through data to print each button
        if (!empty(data.buttons)) {
            var id = 0;
            for (const [key, value] of Object.entries(data.buttons)) {
                innerHtml += '<div class="confirm-box-btn btn-auto-' + value.color + '" confirm-btn-id="' + id + '" pointer">' + value.text + '</div>';
                id++;
            }
        }

        // Close container for buttons
        innerHtml += '</div>'

        // Close base html
        innerHtml += '</div>'

        // Append inner html to confirm box container
        confirmBoxHtml += innerHtml;

        // Close confirm box container
        confirmBoxHtml += '</div>'

        /**
         *  If there is already a confirm box with the same id, do not remove it to avoid blinking
         *  but replace its content
         */
        if (!empty(data.id) && $('#confirm-box').length > 0 && $('#confirm-box').attr('confirm-box-id') == data.id) {
            // Replace confirm box inner content
            $('#confirm-box[confirm-box-id="' + data.id + '"]').html(innerHtml);
        } else {
            // Remove any existing confirm box
            $("#confirm-box").remove();

            // Append html to footer
            $('footer').append(confirmBoxHtml);

            // Set confirm box id if specified
            if (!empty(data.id)) {
                $('#confirm-box').attr('confirm-box-id', data.id);
            }

            // Show confirm box
            $('#confirm-box').css({
                visibility: 'visible'
            }).promise().done(function () {
                $('#confirm-box').animate({
                    right: '0'
                }, 150)
            });
        }

        // If a button is clicked
        $('.confirm-box-btn').click((event) => {
            // Get button id
            var id = $(event.currentTarget).attr('confirm-btn-id');

            // Get function from data
            if (empty(data.buttons[id].callback)) {
                myalert.print('Error: no function specified for this button', 'error');
                return;
            }

            // Execute function
            data.buttons[id].callback();

            // Close confirm box unless closeBox is set to false
            if (empty(data.buttons[id].closeBox) || (!empty(data.buttons[id].closeBox) && data.buttons[id].closeBox == true)) {
                this.close();
            }
        });

        // If 'cancel' choice is clicked
        $('.confirm-box-cancel-btn').click(() => {
            this.close();
        });
    }

    /**
     * Close the confirm box
     */
    close()
    {
        $('#confirm-box').animate({
            right: '-1000px'
        }, 150).promise().done(function () {
            $('#confirm-box').remove();
        });
    }
}
