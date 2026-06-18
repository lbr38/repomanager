/**
 *  Load a KPI card content asynchronously via ajax
 *  - A container element with class "kpi-card-ajax" and a unique id must exist in the HTML
 *  - The id maps to a vars file on the server: controllers/Layout/Kpi/vars/<id>.vars.inc.php
 *  - The value is rendered into the child element with class "kpi-value"
 *  - An optional auto-update can be enabled with the "autoupdate" and "interval" HTML attributes
 */
class KPICard
{
    // Static registry to store all KPI card instances for external access
    static instances = {};

    constructor(id, autoUpdate = false, autoUpdateInterval = 30000)
    {
        this.id                 = id;
        this.autoUpdate         = autoUpdate;
        this.autoUpdateInterval = autoUpdateInterval;
        this.value              = null;
        this.options            = {};
        this.setIntervalId      = null;
        this.defaultIcon        = null;

        // Preserve the default icon defined in HTML so it can be restored
        this.captureDefaultIcon();

        // Register this instance in the static registry
        KPICard.instances[this.id] = this;

        // Load the card content
        this.load();

        // Start auto-update if enabled
        this.startAutoUpdate();
    }

    /**
     *  Fetch KPI data from the server
     */
    get()
    {
        return new Promise((resolve, reject) => {
            ajaxRequest(
                // Controller:
                'kpi',
                // Action:
                'get',
                // Data:
                {
                    id: this.id,
                    sourceGetParameters: getGetParams()
                },
                // Print success alert:
                false,
                // Print error alert:
                'console'
            ).then(() => {
                // Parse the response and store it in the class properties
                this.value   = jsonValue.message.value;
                this.options = jsonValue.message.options || {};

                // Resolve promise
                resolve('KPI data retrieved');
            }).catch(error => {
                // Stop auto-update to prevent further errors
                this.stopAutoUpdate();

                // Display error state in the card
                this.renderError(error);

                // Reject promise
                reject('Failed to get KPI data: ' + error);
            });
        });
    }

    /**
     *  Load (fetch + render) the KPI card
     */
    async load()
    {
        try {
            await this.get();
            this.render();
        } catch (error) {
            // Error already rendered in get()
            console.error(error);
        }
    }

    /**
     *  Render the KPI value into the card
     */
    render()
    {
        const card = document.querySelector('#' + this.id);
        if (!card) {
            console.warn('KPICard.render: container not found for id=' + this.id);
            return;
        }

        const valueElement = card.querySelector('.kpi-value');
        if (!valueElement) {
            console.warn('KPICard.render: .kpi-value element not found in id=' + this.id);
            return;
        }

        // Set the value
        valueElement.textContent = this.value;

        // Apply an optional color sent by the server
        if (this.options.color) {
            valueElement.style.color = this.options.color;
        } else {
            valueElement.style.color = '';
        }

        // Apply an optional icon sent by the server
        this.renderIcon(card);
    }

    /**
     *  Render an error state into the card
     */
    renderError(error)
    {
        const card = document.querySelector('#' + this.id);
        if (!card) return;

        const valueElement = card.querySelector('.kpi-value');
        if (!valueElement) return;

        valueElement.innerHTML = '<span class="font-size-14 lowopacity-cst" title="' + error + '">N/A</span>';
    }

    /**
     *  Start auto-updating the KPI card
     */
    startAutoUpdate()
    {
        if (!this.autoUpdate) return;

        this.setIntervalId = setInterval(async () => {
            await this.load();
        }, this.autoUpdateInterval);
    }

    /**
     *  Stop auto-updating the KPI card
     */
    stopAutoUpdate()
    {
        if (this.setIntervalId) {
            clearInterval(this.setIntervalId);
            this.setIntervalId = null;
        }
    }

    /**
     *  Restart auto-updating the KPI card
     */
    restartAutoUpdate()
    {
        this.stopAutoUpdate();
        this.startAutoUpdate();
    }

    /**
     *  Get the icon element of the KPI card
     */
    getIconElement(card = null)
    {
        const cardElement = card || document.querySelector('#' + this.id);
        if (!cardElement) return null;

        return cardElement.querySelector('img');
    }

    /**
     *  Preserve the default icon from the HTML template
     */
    captureDefaultIcon()
    {
        const iconElement = this.getIconElement();
        if (!iconElement) return;

        this.defaultIcon = {
            src: iconElement.getAttribute('src') || '',
            alt: iconElement.getAttribute('alt') || '',
            title: iconElement.getAttribute('title') || ''
        };
    }

    /**
     *  Apply a backend-provided icon if specified, otherwise restore the default icon
     */
    renderIcon(card)
    {
        const iconElement = this.getIconElement(card);
        if (!iconElement) return;

        if (this.options.icon) {
            iconElement.setAttribute('src', '/assets/icons/' + this.options.icon);

            if (this.options.iconAlt) {
                iconElement.setAttribute('alt', this.options.iconAlt);
            } else if (this.defaultIcon) {
                iconElement.setAttribute('alt', this.defaultIcon.alt);
            }

            if (this.options.iconTitle) {
                iconElement.setAttribute('title', this.options.iconTitle);
            } else if (this.defaultIcon) {
                if (this.defaultIcon.title) {
                    iconElement.setAttribute('title', this.defaultIcon.title);
                } else {
                    iconElement.removeAttribute('title');
                }
            }

            return;
        }

        if (!this.defaultIcon) return;

        iconElement.setAttribute('src', this.defaultIcon.src);
        iconElement.setAttribute('alt', this.defaultIcon.alt);

        if (this.defaultIcon.title) {
            iconElement.setAttribute('title', this.defaultIcon.title);
        } else {
            iconElement.removeAttribute('title');
        }
    }
}

/**
 *  Get a KPI card instance by ID (external access)
 */
KPICard.getInstance = function(id) {
    return KPICard.instances[id] || null;
};

/**
 *  Auto-initialize all ajax-loaded KPI cards on page load
 */
$(document).ready(function () {
    $('.kpi-card-ajax').each(function () {
        const id = $(this).attr('id');

        if (!id) {
            console.warn('KPICard: a .kpi-card-ajax element has no id, skipping');
            return;
        }

        // Avoid creating duplicate instances
        if (KPICard.instances[id]) return;

        const autoUpdate         = $(this).attr('autoupdate') === 'true';
        const autoUpdateInterval = Number($(this).attr('interval')) || 30000;

        new KPICard(id, autoUpdate, autoUpdateInterval);
    });
});
