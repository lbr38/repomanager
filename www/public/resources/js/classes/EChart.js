// import * as echarts from 'echarts';

/**
 *  Create charts with ECharts
 *  - A <div> element with id="<chartID>" must exist in the HTML
 *  - The server must return all the data needed to create the chart (.vars.inc.php file must exist)
 *  - (optional) A loading spinner with id="<chartID>-loading" must exist in the HTML
 */
class EChart
{
    // Static registry to store all chart instances for external access
    static instances = {};

    // If on mobile, default to 1 day range, otherwise 3 days
    constructor(type, id, autoUpdate = true, autoUpdateInterval = 15000, days = 1, wasInNaturalState = true, periodChanged = false, preservedCurrentType = null)
    {
        this.id                 = id;
        this.type               = type;
        this.currentType        = preservedCurrentType || type; // Current type (can change with magicType)
        this._preservedColors   = null; // Store colors when switching types
        this.autoUpdate         = autoUpdate;
        this.autoUpdateInterval = autoUpdateInterval;
        this.days               = days;
        this._wasInNaturalState = wasInNaturalState;
        this._periodChanged     = periodChanged;
        this.datasets           = [];
        this.labels             = [];
        this.chartOptions       = [];
        this.animate            = '';

        // Default options (will be cloned before use)
        this.baseOptions = {
            title: {
                text: '',
                left: 'left',
                textStyle: {
                    color: '#8A99AA',
                    fontFamily: 'Roboto',
                    fontSize: 16,
                }
            },
            tooltip: {
                show: true,
                trigger: 'item',
                confine: true, // Force tooltip to stay within container boundaries
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                textStyle: {
                    color: '#fff'
                },
                formatter: (params) => {
                    if (!params) return '';
                    
                    // For pie charts
                    if (params.seriesType === 'pie') {
                        return `${params.marker} ${params.name}: ${params.value} (${params.percent}%)`;
                    }
                    
                    // For line charts (existing code)
                    if (params.length === 0) return '';
                    
                    const timestamp = params[0].axisValue;
                    const d = new Date(Number(timestamp));
                    let result = d.toLocaleString(undefined, {
                        year: 'numeric', month: 'short', day: '2-digit',
                        hour: '2-digit', minute: '2-digit', second: '2-digit',
                        hour12: false
                    }) + '<br/>';
                    
                    params.forEach(param => {
                        let value = param.value[1];
                        // Check if this is an active/inactive chart (values are 0 or 1)
                        if ((value === 0 || value === 1) && EChart.formatters.activeState) {
                            value = EChart.formatters.activeState(value);
                        }
                        result += `${param.marker} ${param.seriesName}: ${value}<br/>`;
                    });
                    
                    return result;
                }
            },
            legend: {
                show: false,
                type: 'scroll',
                bottom: '0px',
                icon: 'circle',
                itemWidth: 12,
                itemHeight: 12,
                itemGap: 15,
                textStyle: {
                    fontFamily: 'Roboto',
                    fontSize: 14,
                    color: '#8A99AA'
                },
            },
            grid: {
                show: false,
                top: '60px',
                left: '10px',
                right: '10px',
                bottom: '10px',
                width: 'auto',
                containLabel: true
            },
            toolbox: {
                right: '0px',
                top: '0px',
                showTitle: true,
                feature: {
                    dataZoom: {
                        show: false,
                        yAxisIndex: 'none'
                    },
                    dateView: {
                        show: false,
                    },
                    brush: {
                        show: false
                    },
                    restore: {
                        show: true
                    },
                    saveAsImage: {
                        show: true,
                    },
                    magicType: {
                        show: false, // Disabled by default, enabled conditionally
                        type: ['line', 'bar']
                    }
                },
                iconStyle: {
                    borderColor: '#8A99AA',
                    borderWidth: 1.5,
                    borderCap: 'round'
                },
                emphasis: {
                    iconStyle: {
                        borderColor: '#FFFFFF',
                        textPadding: 10
                    }
                }
            },
            xAxis: {
                type: 'time',
                boundaryGap: false,
                axisLabel: {
                    color: '#8A99AA',
                    formatter: (value) => {
                        const d = new Date(value);
                        return d.toLocaleString(undefined, {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        });
                    }
                },
                axisLine: {
                    lineStyle: {
                        color: '#8A99AA'
                    }
                }
            },
            yAxis: {
                type: 'value',
                axisLabel: {
                    color: '#8A99AA'
                },
                axisLine: {
                    lineStyle: {
                        color: '#8A99AA'
                    }
                },
                splitLine: {
                    show: false
                }
            },
            dataZoom: [
                {
                    type: 'inside',
                    start: 80,
                    end: 100
                },
                {
                    type: 'slider',
                    show: false,
                    start: 80,
                    end: 100,
                    height: 32,
                    bottom: 0,
                    showPlayBtn: false,
                    textStyle: {
                        color: '#8A99AA'
                    },
                    borderColor: '#8a99aa54',
                    borderRadius: 4,
                    brushSelect: false
                }
            ],
            series: []
        };

        // To store the SetInterval Id for each chart
        this.setIntervalId = {};

        // Register this instance in the static registry
        EChart.instances[this.id] = this;

        // Create or update the chart
        this.createOrUpdateChart(id);

        // Start auto-update
        this.startAutoUpdate();
    }

    /**
     * Start auto-updating the chart
     */
    startAutoUpdate()
    {
        // If autoUpdate is enabled, set an interval to update the chart
        // Default: update every 10 seconds
        if (!this.autoUpdate) return;

        this.setIntervalId[this.id] = setInterval(async () => {
            console.info('EChart.startAutoUpdate: updating chart for id=' + this.id);

            // Get chart data
            await this.get(this.id);

            const chartElement = document.querySelector("#" + this.id);
            const chart = chartElement?._chartInstance;
            if (!chart) return;

            // Get current dataZoom state to preserve zoom/pan
            const currentOption = chart.getOption();
            const currentDataZoom = currentOption.dataZoom;
            
            // Check if chart is in natural state (not zoomed)
            const isNaturalState = this.isInNaturalState(currentDataZoom);

            // Build series data
            const series = this.buildSeries();

            // Update chart with new data
            chart.setOption({
                series: series
            });

            // Only restore zoom state if user has zoomed/panned (not in natural state)
            if (!isNaturalState && currentDataZoom) {
                chart.setOption({
                    dataZoom: currentDataZoom
                });
            }

        }, this.autoUpdateInterval);
    }

    /**
     * Stop auto-updating the chart
     * @param {string} id - Optional chart ID, uses this.id if not provided
     */
    stopAutoUpdate(id = null)
    {
        const chartId = id || this.id;
        // Clear the interval to stop auto-updating
        if (this.setIntervalId[chartId]) {
            console.info('EChart.stopAutoUpdate: stopping auto-update for chart id=' + chartId);
            clearInterval(this.setIntervalId[chartId]);
            delete this.setIntervalId[chartId];
        }
    }

    /**
     * Restart auto-updating the chart
     */
    restartAutoUpdate()
    {
        this.stopAutoUpdate();
        this.startAutoUpdate();
    }

    /**
     * Check if chart is in natural (unzoomed) state
     */
    isInNaturalState(dataZoom)
    {
        if (!dataZoom || dataZoom.length === 0) return true;
        
        // Consider natural if showing most of the data (e.g., > 90%)
        const insideZoom = dataZoom.find(dz => dz.type === 'inside') || dataZoom[0];
        if (!insideZoom) return true;
        
        const range = (insideZoom.end || 100) - (insideZoom.start || 0);
        return range > 90;
    }

    /**
     * Get chart data by ID
     * @param {*} id
     * @returns
     */
    get(id)
    {
        return new Promise((resolve, reject) => {
            ajaxRequest(
                // Controller:
                'chart',
                // Action:
                'get',
                // Data:
                {
                    id: id,
                    days: this.days,
                    sourceGetParameters: getGetParams()
                },
                // Print success alert:
                false,
                // Print error alert:
                true
            ).then(() => {
                // Parse the response and store it in the class properties
                this.datasets     = jsonValue.message.datasets;
                this.labels       = jsonValue.message.labels;
                this.chartOptions = jsonValue.message.options;

                // For debugging purposes only
                // console.log("datasets: " + JSON.stringify(this.datasets));
                // console.log("labels: " + JSON.stringify(this.labels));
                // console.log("options: " + JSON.stringify(this.chartOptions));

                // Resolve promise
                resolve('Chart data retrieved');
            }).catch(error => {
                // Stop auto-update to prevent further errors
                this.stopAutoUpdate();

                // Remove loading spinner
                $('#' + id + '-loading').remove();

                // Replace chart with error message
                $('#' + id).html('<div class="flex align-item-center justify-center height-100"><p>Failed to get chart data: ' + error.toLowerCase() + '</p></div>');

                // Reject promise
                reject('Failed to get chart data: ' + error);

                return;                
            });
        });
    }

    /**
     * Build series array formatted for EChart
     */
    buildSeries()
    {
        if (this.currentType === 'line') {
            return this.datasets.map((dataset, datasetIndex) => {
                const data = dataset.data.map((v, i) => [this.labels[i], v]);
                // Use preserved color first, then dataset color, then default
                const lineColor = (this._preservedColors && this._preservedColors[datasetIndex]) || 
                                 dataset.color || '#15bf7f';
                
                // Determine if we have large dataset
                const isLargeDataset = this.days > 3; // Consider large if showing more than 3 days of data (configurable threshold)
                
                return {
                    name: dataset.name,
                    type: 'line',
                    color: lineColor,
                    smooth: true,
                    symbol: 'none',
                    sampling: isLargeDataset ? 'lttb' : undefined, // Use LTTB sampling for large datasets
                    lineStyle: {
                        width: 2,
                        color: lineColor
                    },
                    areaStyle: {
                        opacity: 0.25,
                        color: lineColor
                    },
                    data: data
                };
            });
        }

        if (this.currentType === 'bar') {
            return this.datasets.map((dataset, datasetIndex) => {
                const data = dataset.data.map((v, i) => {
                    const item = [this.labels[i], v];

                    // If we have individual colors defined, apply them
                    if (dataset.colors && dataset.colors[i]) {
                        // For bar charts, we need to return an object with itemStyle
                        return {
                            value: item,
                            itemStyle: {
                                borderRadius: [4, 4, 0, 0],
                                color: dataset.colors[i]
                            }
                        };
                    }
                    
                    return item;
                });

                // Use preserved color first, then dataset color, then default
                const barColor = (this._preservedColors && this._preservedColors[datasetIndex]) || 
                                dataset.color || '#15bf7f';

                return {
                    name: dataset.name,
                    type: 'bar',
                    color: barColor, // Use preserved/dataset/default color
                    barMaxWidth: 30, // Maximum width of each bar in pixels
                    itemStyle: {
                        borderRadius: [4, 4, 0, 0],
                        color: barColor // Use same color
                    },
                    emphasis: {
                        itemStyle: {
                            opacity: 0.8
                        }
                    },
                    data: data
                };
            });
        }

        if (this.currentType === 'barHorizontal') {
            return this.datasets.map(dataset => {
                const data = dataset.data.map((v, i) => {
                    // For horizontal bars, just use the value (not [label, value])
                    // Labels are handled by yAxis.data
                    const item = v;

                    // If we have individual colors defined, apply them
                    if (dataset.colors && dataset.colors[i]) {
                        // For bar charts, we need to return an object with itemStyle
                        return {
                            value: item,
                            itemStyle: {
                                borderRadius: [0, 4, 4, 0], // Right rounded for horizontal
                                color: dataset.colors[i]
                            }
                        };
                    }
                    
                    return item;
                });

                return {
                    name: dataset.name,
                    type: 'bar',
                    color: dataset.color, // Default color if no individual colors
                    barMaxWidth: 30, // Maximum height for horizontal bars
                    itemStyle: {
                        borderRadius: [0, 4, 4, 0], // Right rounded for horizontal
                        color: dataset.color // Default color
                    },
                    emphasis: {
                        itemStyle: {
                            opacity: 0.8
                        }
                    },
                    data: data
                };
            });
        }

        if (this.currentType === 'pie') {
            return this.datasets.map(dataset => {
                const data = dataset.data.map((v, i) => {
                    const item = {
                        name: this.labels[i],
                        value: v
                    };

                    // Add custom color if defined
                    if (dataset.colors && dataset.colors[i]) {
                        item.itemStyle = {
                            color: dataset.colors[i]
                        };
                    }

                    return item;
                });

                return {
                    name: dataset.name,
                    type: 'pie',
                    radius: ['0%', '70%'],
                    center: ['50%', '50%'],
                    itemStyle: {
                        borderRadius: 3
                    },
                    label: {
                        color: '#8A99AA'
                    },
                    data: data
                };
            });
        }

        if (this.currentType === 'nightingale') {
            return this.datasets.map(dataset => {
                const data = dataset.data.map((v, i) => {
                    const item = {
                        name: this.labels[i],
                        value: v
                    };

                    // Add custom color if defined
                    if (dataset.colors && dataset.colors[i]) {
                        item.itemStyle = {
                            color: dataset.colors[i]
                        };
                    }

                    return item;
                });

                // Get radius values from chartOptions if defined, otherwise use defaults
                let innerRadius = '0%';
                let outerRadius = '65%';
                
                if (this.chartOptions) {
                    if (this.chartOptions.innerRadius !== undefined) {
                        innerRadius = this.chartOptions.innerRadius;
                    }
                    if (this.chartOptions.outerRadius !== undefined) {
                        outerRadius = this.chartOptions.outerRadius;
                    }
                }

                return {
                    name: dataset.name,
                    type: 'pie',
                    roseType: 'radius',
                    radius: [innerRadius, outerRadius], // Configurable radius values
                    center: ['50%', '50%'],
                    itemStyle: {
                        borderRadius: 2
                    },
                    label: {
                        color: '#8A99AA',
                        position: 'outside'
                    },
                    labelLine: {
                        show: true,
                        lineStyle: {
                            color: '#8A99AA'
                        }
                    },
                    data: data
                };
            });
        }

        if (this.type === 'doughnut') {
            return this.datasets.map(dataset => {
                const data = dataset.data.map((v, i) => {
                    const item = {
                        name: this.labels[i],
                        value: v
                    };

                    // Add custom color if defined
                    if (dataset.colors && dataset.colors[i]) {
                        item.itemStyle = {
                            color: dataset.colors[i]
                        };
                    }

                    return item;
                });

                // Get radius values from chartOptions if defined, otherwise use defaults
                let innerRadius = '40%';
                let outerRadius = '90%';
                
                if (this.chartOptions) {
                    if (this.chartOptions.innerRadius !== undefined) {
                        innerRadius = this.chartOptions.innerRadius;
                    }
                    if (this.chartOptions.outerRadius !== undefined) {
                        outerRadius = this.chartOptions.outerRadius;
                    }
                }

                const seriesConfig = {
                    name: dataset.name,
                    type: 'pie',
                    radius: [innerRadius, outerRadius], // Configurable radius values
                    center: ['50%', '50%'],
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 3
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    labelLine: {
                        show: false
                    },
                    data: data
                };

                // Handle emphasis (hover effects)
                if (this.chartOptions.emphasis?.disabled === true) {
                    // Completely disable hover effects
                    seriesConfig.emphasis = {
                        disabled: true
                    };
                } else {
                    // Default emphasis behavior
                    seriesConfig.emphasis = {
                        label: {
                            show: false
                        }
                    };
                }

                return seriesConfig;
            });
        }

        if (this.currentType === 'points' || this.currentType === 'scatter') {
            // Check if labels should be displayed instead of or with points
            const showAsLabels = this.chartOptions?.showAsLabels || false;
            const showBothLabelsAndPoints = this.chartOptions?.showBothLabelsAndPoints || false;
            
            if (showAsLabels && !showBothLabelsAndPoints) {
                // Using individual series approach for adaptive positioning
                
                // Create a separate series for each point to control individual label positioning
                const allSeries = [];
                
                this.datasets.forEach((dataset, datasetIndex) => {
                    const pointColor = (this._preservedColors && this._preservedColors[datasetIndex]) || dataset.color || '#15bf7f';
                    
                    dataset.data.forEach((value, pointIndex) => {
                        // Determine position for this specific point
                        let position = 'top';
                        if (pointIndex === 0) {
                            position = 'right';
                        } else if (pointIndex === dataset.data.length - 1) {
                            position = 'left'; // Last point: completely to the left
                        }
                        
                        // Point positioning: first=insideTopRight, last=left, others=top
                        const seriesConfig = {
                            name: dataset.name,
                            type: 'scatter',
                            color: pointColor,
                            symbol: 'circle',
                            symbolSize: 8,
                            itemStyle: {
                                color: pointColor,
                                opacity: 1,
                                // borderColor: '#ffffff',
                                borderWidth: 1
                            },
                            emphasis: {
                                itemStyle: {
                                    opacity: 1,
                                    borderWidth: 2
                                }
                            },
                            data: [{
                                value: [this.labels[pointIndex], value],
                                snapshotId: dataset.snapshotIds ? dataset.snapshotIds[pointIndex] : null
                            }],
                            label: {
                                show: true,
                                position: position,
                                color: this.chartOptions?.labelColor || '#FFFFFF',
                                fontSize: this.chartOptions?.labelFontSize || 16,
                                fontWeight: this.chartOptions?.labelFontWeight || 'bold',
                                fontFamily: this.chartOptions?.labelFontFamily || 'Arial, sans-serif',
                                backgroundColor: this.chartOptions?.labelBackground || '#000000',
                                borderRadius: this.chartOptions?.labelBorderRadius || 8,
                                padding: this.chartOptions?.labelPadding || [10, 15],
                                formatter: (params) => {
                                    const timestamp = params.value[0];
                                    const date = new Date(Number(timestamp));
                                    
                                    const dateFormat = this.chartOptions?.labelDateFormat || 'fr-FR';
                                    const dateOptions = this.chartOptions?.labelDateOptions || {
                                        day: '2-digit',
                                        month: '2-digit',
                                        year: 'numeric'
                                    };
                                    
                                    let formattedDate = date.toLocaleDateString(dateFormat, dateOptions);
                                    
                                    // Remplacer le séparateur par celui configuré (défaut: tiret)
                                    const separator = this.chartOptions?.labelDateSeparator || '-';
                                    if (separator !== '/') {
                                        formattedDate = formattedDate.replace(/\//g, separator);
                                    }
                                    
                                    return formattedDate;
                                }
                            },
                            // Hide from legend since we're creating multiple series for one dataset
                            legendHoverLink: false
                        };

                        // Add borders and shadows if enabled
                        if (this.chartOptions?.labelBorder !== false && this.chartOptions?.labelBorderWidth !== 0) {
                            seriesConfig.label.borderColor = this.chartOptions?.labelBorderColor || '#FFFFFF';
                            seriesConfig.label.borderWidth = this.chartOptions?.labelBorderWidth || 2;
                        }

                        if (this.chartOptions?.labelShadow !== false) {
                            seriesConfig.label.shadowColor = this.chartOptions?.labelShadowColor || 'rgba(0, 0, 0, 0.8)';
                            seriesConfig.label.shadowBlur = this.chartOptions?.labelShadowBlur || 5;
                            seriesConfig.label.shadowOffsetX = this.chartOptions?.labelShadowOffsetX || 2;
                            seriesConfig.label.shadowOffsetY = this.chartOptions?.labelShadowOffsetY || 2;
                        }
                        
                        allSeries.push(seriesConfig);
                    });
                });
                
                return allSeries;
            }
            
            // Original logic for non-labels mode and labels+points mode
            return this.datasets.map((dataset, datasetIndex) => {
                const data = dataset.data.map((v, i) => ({
                    value: [this.labels[i], v],
                    snapshotId: dataset.snapshotIds ? dataset.snapshotIds[i] : null
                }));
                // Use preserved color first, then dataset color, then default
                const pointColor = (this._preservedColors && this._preservedColors[datasetIndex]) || dataset.color || '#15bf7f';
                
                // Get symbol size from chartOptions if defined, otherwise use default
                const symbolSize = this.chartOptions?.symbolSize || 8;
                
                // const labelFormat = this.chartOptions?.labelFormat || 'date'; // 'date', 'value', 'name', 'custom'
                
                // Configure the series
                const seriesConfig = {
                    name: dataset.name,
                    type: 'scatter',
                    color: pointColor,
                    symbol: 'circle',
                    data: data
                };

                // Configuration spécifique selon le mode d'affichage
                if (showBothLabelsAndPoints) {
                    // Mode points + labels
                    seriesConfig.symbolSize = symbolSize;
                    seriesConfig.itemStyle = {
                        color: pointColor,
                        opacity: 1,
                        // borderColor: '#ffffff',
                        borderWidth: 1
                    };
                    seriesConfig.emphasis = {
                        itemStyle: {
                            opacity: 1,
                            borderWidth: 2
                        }
                    };
                    
                    seriesConfig.label = {
                        show: true,
                        position: this.chartOptions?.labelPosition || 'top',
                        color: this.chartOptions?.labelColor || '#000000',
                        fontSize: this.chartOptions?.labelFontSize || 12,
                        fontWeight: this.chartOptions?.labelFontWeight || 'bold',
                        fontFamily: this.chartOptions?.labelFontFamily || 'Arial, sans-serif',
                        backgroundColor: this.chartOptions?.labelBackground || 'rgba(255, 255, 255, 0.9)',
                        borderRadius: this.chartOptions?.labelBorderRadius || 4,
                        padding: this.chartOptions?.labelPadding || [6, 10],
                        formatter: (params) => {
                            const timestamp = params.value[0];
                            const date = new Date(Number(timestamp));
                            
                            // Format de date configurable via PHP
                            const dateFormat = this.chartOptions?.labelDateFormat || 'fr-FR';
                            const dateOptions = this.chartOptions?.labelDateOptions || {
                                day: '2-digit',
                                month: '2-digit'
                            };
                            
                            let formattedDate = date.toLocaleDateString(dateFormat, dateOptions);
                            
                            // Remplacer le séparateur par celui configuré (défaut: tiret)
                            const separator = this.chartOptions?.labelDateSeparator || '-';
                            if (separator !== '/') {
                                formattedDate = formattedDate.replace(/\//g, separator);
                            }
                            
                            return formattedDate;
                        }
                    };

                    // Ajouter les bordures seulement si activées
                    if (this.chartOptions?.labelBorder !== false && this.chartOptions?.labelBorderWidth !== 0) {
                        seriesConfig.label.borderColor = this.chartOptions?.labelBorderColor || '#333333';
                        seriesConfig.label.borderWidth = this.chartOptions?.labelBorderWidth || 1;
                    }

                    // Ajouter l'ombre seulement si activée
                    if (this.chartOptions?.labelShadow !== false) {
                        seriesConfig.label.shadowColor = this.chartOptions?.labelShadowColor || 'rgba(0, 0, 0, 0.3)';
                        seriesConfig.label.shadowBlur = this.chartOptions?.labelShadowBlur || 3;
                        seriesConfig.label.shadowOffsetX = this.chartOptions?.labelShadowOffsetX || 1;
                        seriesConfig.label.shadowOffsetY = this.chartOptions?.labelShadowOffsetY || 1;
                    }
                } else {
                    // Mode points uniquement (défaut)
                    seriesConfig.symbolSize = symbolSize;
                    seriesConfig.itemStyle = {
                        color: pointColor,
                        opacity: 0.9,
                        borderColor: '#ffffff',
                        borderWidth: 1
                    };
                    seriesConfig.emphasis = {
                        itemStyle: {
                            opacity: 1,
                            borderWidth: 2
                        }
                    };
                }
                
                return seriesConfig;
            });
        }
    }

    /**
     * Generic method to create or update a chart
     * @param {string} id - Chart ID
     * @returns {Promise}
     */
    async createOrUpdateChart(id)
    {
        await this.get(id);

        const chartElement = document.querySelector("#" + id);
        const existingChart = chartElement?._chartInstance;

        if (existingChart) {
            return this.updateChart(existingChart);
        }

        return this.createChart(id);
    }

    /**
     * Create the chart for the first time
     */
    createChart(id)
    {
        console.info('EChart.createChart: creating chart for id=' + id);

        // Get the container element and bail out if missing
        const chartElement = document.querySelector("#" + id);
        if (!chartElement) {
            console.warn('EChart.createChart: container not found for id=', id);
            return;
        }

        // Clone base options
        const options = JSON.parse(JSON.stringify(this.baseOptions));

        // Build series data
        const series = this.buildSeries();
        options.series = series;

        // For pie charts, hide axes and grid
        if (this.type === 'pie') {
            options.xAxis.show = false;
            options.yAxis.show = false;
            options.grid.show = false;
            // Disable dataZoom to avoid conflicts with page scroll
            options.dataZoom = [];
        }

        // For nightingale charts, hide axes and grid (same as pie)
        if (this.type === 'nightingale') {
            options.xAxis.show = false;
            options.yAxis.show = false;
            options.grid.show = false;
            // Disable dataZoom to avoid conflicts with page scroll
            options.dataZoom = [];
        }

        // For doughnut charts, hide axes and grid (same as pie)
        if (this.type === 'doughnut') {
            options.xAxis.show = false;
            options.yAxis.show = false;
            options.grid.show = false;
            // Disable dataZoom to avoid conflicts with page scroll
            options.dataZoom = [];
        }

        // For line charts, adjust tooltip trigger and add axis pointer
        if (this.type === 'line') {
            options.tooltip.trigger = 'axis';
            options.tooltip.axisPointer = {
                type: 'line',
                animation: false,
                label: {
                    backgroundColor: '#505765'
                }
            };
        }

        // For points/scatter charts, adjust tooltip trigger and axis configuration
        if (this.type === 'points' || this.type === 'scatter') {
            options.tooltip.trigger = 'item';
            options.tooltip.formatter = (params) => {
                if (!params) return '';
                
                const timestamp = params.value[0];
                const value = params.value[1];
                const d = new Date(Number(timestamp));
                
                // Skip generic series names like 'series0', 'series1', etc.
                const shouldShowSeriesName = params.seriesName && 
                    !params.seriesName.match(/^series\d+$/i) &&
                    !params.seriesName.toLowerCase().includes('series');
                
                let result = '';
                if (shouldShowSeriesName) {
                    result += '<b>' + params.seriesName + '</b><br/>';
                }

                // result += 'Time: ' + d.toLocaleString(undefined, {
                //     year: 'numeric', month: 'short', day: '2-digit',
                //     hour: '2-digit', minute: '2-digit', second: '2-digit',
                //     hour12: false
                // }) + '<br/>';
                
                // Configurable unit and precision from PHP options
                const unit = this.chartOptions?.tooltip?.valueUnit || '';
                const precision = this.chartOptions?.tooltip?.valuePrecision || 2;
                const formattedValue = value.toFixed(precision);
                
                result += unit ? `${formattedValue} ${unit}` : formattedValue;
                
                return result;
            };

            // Ajuster les marges si des labels sont affichés pour éviter le débordement
            if (this.chartOptions?.showAsLabels || this.chartOptions?.showBothLabelsAndPoints) {
                options.grid.containLabel = true; // ECharts ajuste automatiquement pour contenir les labels
            }
        }

        // For bar charts, adjust axis configuration
        if (this.type === 'bar') {
            // For bar charts, we usually use categories on the X axis
            options.xAxis.type = 'category';
            options.xAxis.data = this.labels;
            options.xAxis.boundaryGap = true; // Add space around bars
            options.xAxis.axisLabel.rotate = 45;
            options.xAxis.axisLabel.interval = 0; // Show all labels
            options.xAxis.axisLabel.fontSize = 12;
            options.xAxis.axisLabel.textStyle = {
                color: '#8A99AA'
            };
        }

        // For horizontal bar charts, just swap the axes (same as vertical but inverted)
        if (this.type === 'barHorizontal') {
            // Y axis: categories (instead of X for vertical bars)
            options.yAxis.type = 'category';
            options.yAxis.data = this.labels;
            options.yAxis.inverse = true; // Invert Y-axis to show first data at top
            options.yAxis.axisLabel = {
                color: '#8A99AA'
            };

            // X axis: values (instead of Y for vertical bars)
            options.xAxis.type = 'value';
            options.xAxis.boundaryGap = [0, 0.01];
            options.xAxis.axisLabel = {
                color: '#8A99AA'
            };
            options.xAxis.splitLine = {
                show: false  // Hide vertical grid lines
            };
            delete options.xAxis.data;
            
            // Disable dataZoom to avoid conflicts with page scroll
            options.dataZoom = [];
        }

        // Set title
        if (this.chartOptions.title?.text) {
            options.title.text = this.chartOptions.title.text;
            options.title.left = this.chartOptions.title.align || 'left';
        }

        // Y-axis features
        if (this.chartOptions.yaxis) {
            if (this.chartOptions.yaxis.min !== undefined) {
                options.yAxis.min = this.chartOptions.yaxis.min;
            }
            if (this.chartOptions.yaxis.max !== undefined) {
                options.yAxis.max = this.chartOptions.yaxis.max;
            }

            // If server sends a formatterName, use table lookup
            if (this.chartOptions.yaxis.labels?.formatterName) {
                const formatterFn = EChart.formatters[this.chartOptions.yaxis.labels.formatterName];
                if (formatterFn) {
                    options.yAxis.axisLabel.formatter = formatterFn;
                    
                    // Special case for activeState (binary 0/1 charts)
                    if (this.chartOptions.yaxis.labels.formatterName === 'activeState') {
                        options.yAxis.min = 0;
                        options.yAxis.max = 1;
                        options.yAxis.interval = 1;
                        options.yAxis.splitNumber = 1;
                        
                        // Override tooltip formatter for activeState charts
                        options.tooltip.formatter = (params) => {
                            if (params.length === 0) return '';
                            
                            const timestamp = params[0].axisValue;
                            const d = new Date(Number(timestamp));
                            let result = d.toLocaleString(undefined, {
                                year: 'numeric', month: 'short', day: '2-digit',
                                hour: '2-digit', minute: '2-digit', second: '2-digit',
                                hour12: false
                            }) + '<br/>';
                            
                            params.forEach(param => {
                                // Get the actual value - could be param.value[1] or just param.value
                                let rawValue = Array.isArray(param.value) ? param.value[1] : param.value;
                                const value = EChart.formatters.activeState(Number(rawValue));
                                result += `${param.marker} ${param.seriesName}: ${value}<br/>`;
                            });
                            
                            return result;
                        };
                    }
                }
            }
        }

        // Toolbar show / hide
        if (this.chartOptions.toolbox?.show === false) {
            options.toolbox.show = false;
        }

        // Enable magicType only for line, bar and scatter/points charts
        if (this.type === 'line' || this.type === 'bar' || this.type === 'points' || this.type === 'scatter') {
            options.toolbox.feature.magicType.show = true;
            // Add scatter to the available types if it's a points chart
            if (this.type === 'points' || this.type === 'scatter') {
                options.toolbox.feature.magicType.type = ['line', 'bar', 'scatter'];
            }
        }

        // Enable dataZoom only for line and bar charts
        if (this.type === 'line' || this.type === 'bar') {
            options.toolbox.feature.dataZoom.show = true;
        }

        // Merge tooltip options from server configuration (deep merge for nested properties like axisPointer)
        if (this.chartOptions.tooltip) {
            for (const key in this.chartOptions.tooltip) {
                if (this.chartOptions.tooltip[key] && typeof this.chartOptions.tooltip[key] === 'object' && !Array.isArray(this.chartOptions.tooltip[key])) {
                    options.tooltip[key] = { ...options.tooltip[key], ...this.chartOptions.tooltip[key] };
                } else {
                    options.tooltip[key] = this.chartOptions.tooltip[key];
                }
            }
        }
        
        // Tooltip show / hide (explicit override if needed)
        if (this.chartOptions.tooltip?.show === false) {
            options.tooltip.show = false;
        }

        // Legend
        if (this.chartOptions.legend?.show === true) {
            options.legend.show = true;

            // If legend is show, adjust grid bottom to make room
            options.grid.bottom = '30px';
        }

        // Determine if we have large dataset (used for slider and other optimizations)
        const totalPoints = this.labels.length;
        const isLargeDataset = this.days > 3;

        // DataZoom slider show / hide (auto-enable for large datasets on line/bar charts)
        if (this.chartOptions.dataZoom?.slider?.show === true || 
            (isLargeDataset && totalPoints > 1 && (this.type === 'line' || this.type === 'bar'))) {
            options.dataZoom[1].show = true;
            // Adjust grid bottom to make room for the slider
            options.grid.bottom = '45px';
        }

        // Window size for initial zoom (default 15 points) - only for line and points/scatter charts
        if (this.type === 'line' || this.type === 'points' || this.type === 'scatter') {
            const visibleCount = this.chartOptions?.['init-zoom'] ?? 15;
            
            // If period changed, always show all data so user can see the new time range
            if (this._periodChanged) {
                options.dataZoom[0].start = 0;
                options.dataZoom[0].end = 100;
                options.dataZoom[1].start = 0;
                options.dataZoom[1].end = 100;
            }
            // If the chart was previously zoomed/panned, show all data instead of applying initial zoom
            else if (this._wasInNaturalState === false) {
                options.dataZoom[0].start = 0;
                options.dataZoom[0].end = 100;
                options.dataZoom[1].start = 0;
                options.dataZoom[1].end = 100;
            } else if (isLargeDataset) {
                // For large datasets (> 100 points), show only a percentage initially (about 15-20 days for 6 months)
                const endPercentage = Math.max(10, Math.min(20, (visibleCount / totalPoints) * 100));
                options.dataZoom[0].start = 100 - endPercentage;
                options.dataZoom[0].end = 100;
                options.dataZoom[1].start = 100 - endPercentage;
                options.dataZoom[1].end = 100;
            } else if (totalPoints > visibleCount) {
                // Apply normal initial zoom logic only if chart was in natural state
                // Find the range that contains the most recent data with actual values
                let hasDataInRange = false;
                let startPercent = Math.max(0, ((totalPoints - visibleCount) / totalPoints) * 100);
                
                // Check if there's actual data in the calculated range
                for (let i = Math.floor(totalPoints * startPercent / 100); i < totalPoints; i++) {
                    for (let dataset of this.datasets) {
                        if (dataset.data[i] && dataset.data[i] !== 0) {
                            hasDataInRange = true;
                            break;
                        }
                    }
                    if (hasDataInRange) break;
                }
                
                // If no data in the default range, show the full range instead
                if (!hasDataInRange) {
                    startPercent = 0;
                    options.dataZoom[0].end = 100;
                    options.dataZoom[1].end = 100;
                }
                
                options.dataZoom[0].start = startPercent;
                options.dataZoom[1].start = startPercent;
            } else {
                // If we have few data points, show everything
                options.dataZoom[0].start = 0;
                options.dataZoom[0].end = 100;
                options.dataZoom[1].start = 0;
                options.dataZoom[1].end = 100;
            }
        }

        // Initialize EChart with Canvas renderer for better performance
        const chart = echarts.init(chartElement, null, {
            renderer: 'canvas'
        });
        
        // Set options and render
        chart.setOption(options);

        // Force zoom reset if period changed - this ensures consistent behavior
        if (this._periodChanged) {
            // Add a small delay to ensure the chart is fully rendered
            setTimeout(() => {
                chart.dispatchAction({
                    type: 'dataZoom',
                    start: 0,
                    end: 100
                });
            }, 50);
        }

        // Remove spinner
        $('#' + id + '-loading').hide();

        chartElement._chartInstance = chart;

        // Listen for magicType changes to preserve chart type
        chart.on('magictypechanged', (params) => {
            // Store current colors before type change
            const currentOption = chart.getOption();
            if (currentOption.series && currentOption.series.length > 0) {
                this._preservedColors = currentOption.series.map(serie => serie.color);
                console.info('EChart: preserved colors:', this._preservedColors);
            }
            
            this.currentType = params.currentType;
            console.info('EChart: magicType changed to', params.currentType, 'for chart', this.id);
        });

        // Add click event if configured in chartOptions
        if (this.chartOptions.clickCallback?.enabled === true) {
            chart.on('click', (params) => {
                let urlValue = null;
                
                // For points/scatter charts with snapshotId data
                if ((this.type === 'points' || this.type === 'scatter') && 
                    params.componentType === 'series' && 
                    params.data && 
                    params.data.snapshotId) {
                    urlValue = params.data.snapshotId;
                }
                // Fallback to original behavior for other chart types
                else if (params.componentType === 'series' && params.name) {
                    urlValue = params.name;
                }
                
                if (urlValue) {
                    // Build URL with the configured pattern
                    let url = this.chartOptions.clickCallback.url;
                    // Replace placeholder with the value
                    url = url.replace('{value}', encodeURIComponent(urlValue));
                    
                    // Open in new tab or same tab based on configuration
                    if (this.chartOptions.clickCallback.newTab !== false) {
                        window.open(url, '_blank');
                    } else {
                        window.location.href = url;
                    }
                }
            });
        }

        // Handle window resize
        window.addEventListener('resize', () => {
            chart.resize();
        });
    }

    // helper local formatter (use user's locale and local timezone)
    _formatDate(val, withDate = false) {
        const d = new Date(Number(val));

        if (withDate) {
            return d.toLocaleString(undefined, {
                year: 'numeric', month: 'short', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit',
                hour12: false
            });
        }

        return d.toLocaleString(undefined, {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });
    }

    /**
     * Update an existing chart without resetting zoom/pan
     */
    updateChart(chart)
    {
        // Get current dataZoom state to preserve zoom/pan
        const currentOption = chart.getOption();
        const currentDataZoom = currentOption.dataZoom;
        
        // Check if chart is in natural state
        const isNaturalState = this.isInNaturalState(currentDataZoom);

        // Build new series
        const series = this.buildSeries();

        // Update chart with new data
        chart.setOption({
            series: series
        });

        // Only restore zoom if user has zoomed/panned (not in natural state)
        if (!isNaturalState && currentDataZoom) {
            chart.setOption({
                dataZoom: currentDataZoom
            });
        }

        $('#' + this.id + '-loading').hide();
    }
}

/**
 * Static methods for external control of charts
 */

/**
 * Stop auto-update for a specific chart by ID (external access)
 * @param {string} chartId - The chart ID
 */
EChart.stopAutoUpdateById = function(chartId) {
    const instance = EChart.instances[chartId];
    if (instance) {
        instance.stopAutoUpdate();
        return true;
    }
    console.warn('EChart.stopAutoUpdateById: chart not found for id=' + chartId);
    return false;
};

/**
 * Start auto-update for a specific chart by ID (external access)
 * @param {string} chartId - The chart ID
 */
EChart.startAutoUpdateById = function(chartId) {
    const instance = EChart.instances[chartId];
    if (instance) {
        instance.restartAutoUpdate();
        return true;
    }
    console.warn('EChart.startAutoUpdateById: chart not found for id=' + chartId);
    return false;
};

/**
 * Stop auto-update for all charts (external access)
 */
EChart.stopAllAutoUpdates = function() {
    Object.values(EChart.instances).forEach(instance => {
        instance.stopAutoUpdate();
    });
};

/**
 * Start auto-update for all charts (external access)
 */
EChart.startAllAutoUpdates = function() {
    Object.values(EChart.instances).forEach(instance => {
        instance.restartAutoUpdate();
    });
};

/**
 * Get chart instance by ID (external access)
 * @param {string} chartId - The chart ID
 * @returns {EChart|null} - The chart instance or null
 */
EChart.getInstance = function(chartId) {
    return EChart.instances[chartId] || null;
};

/**
 * Clean up chart instance from registry
 * @param {string} chartId - The chart ID
 */
EChart.destroyInstance = function(chartId) {
    console.info('EChart.destroyInstance: destroying chart instance for id=' + chartId);
    const instance = EChart.instances[chartId];
    if (instance) {
        instance.stopAutoUpdate();
        
        // Clean up DOM element reference
        const chartElement = document.querySelector("#" + chartId);
        if (chartElement && chartElement._chartInstance) {
            chartElement._chartInstance.dispose();
            delete chartElement._chartInstance;
        }
        
        delete EChart.instances[chartId];
        return true;
    }
    return false;
};

/**
 * Recreate a chart by destroying and creating a new instance
 * @param {*} type 
 * @param {*} id 
 * @param {*} autoUpdate 
 * @param {*} autoUpdateInterval 
 * @param {*} providedDays - Number of days for the chart. If null, preserves the current chart's days value.
 */
EChart.recreate = function(type, id, autoUpdate = true, autoUpdateInterval = 15000, providedDays = null) {
    // Check if existing chart was in natural state before destroying
    let wasInNaturalState = true;
    let periodChanged = false;
    let preservedCurrentType = type; // Default to original type
    let instance = null; // Declare instance variable
    let days = providedDays; // The final days value to use

    try {
        instance = EChart.instances[id];
        
        // If no days specified, try to preserve current chart's days value
        if (providedDays === null && instance) {
            days = instance.days;
            console.info('EChart.recreate: preserving current days value:', days);
        } else if (providedDays === null) {
            // Fallback if no instance exists
            days = 1;
        }
        
        // Get current chart element for zoom state check
        const chartElement = document.querySelector("#" + id);
        if (chartElement && chartElement._chartInstance && instance) {
            const currentOption = chartElement._chartInstance.getOption();
            if (currentOption && currentOption.dataZoom) {
                wasInNaturalState = instance.isInNaturalState(currentOption.dataZoom);
            }
            
            // Preserve the current chart type (may have been changed by magicType)
            preservedCurrentType = instance.currentType || instance.type;
        }
        
        // Check if period has changed - do this after days value is determined
        if (instance) {
            const oldDays = Number(instance.days);
            const newDays = Number(days);
            periodChanged = oldDays !== newDays;
            
            if (periodChanged) {
                console.info('EChart.recreate: period changed from', oldDays, 'to', newDays, '- will reset zoom');
            }
        }
    } catch (error) {
        console.warn('EChart.recreate: Error checking zoom state, defaulting to natural state', error);
        wasInNaturalState = true;
        periodChanged = false;
        preservedCurrentType = type;
        // Ensure we have a fallback days value
        if (days === null) {
            days = 1;
        }
    }
    
    if (EChart.destroyInstance(id)) {
        // Make spinner visible before creating new chart
        $('#' + id + '-loading').show();

        // Create new instance and pass the preserved type directly to constructor
        const newInstance = new EChart(type, id, autoUpdate, autoUpdateInterval, days, wasInNaturalState, periodChanged, preservedCurrentType);
        
        // Also preserve colors if they existed
        if (instance && instance._preservedColors) {
            newInstance._preservedColors = instance._preservedColors;
        }
    }
}

/**
 * Register available formatters (to avoid eval)
 */
EChart.formatters = {
    // Example usage: formatterName: "activeState"
    activeState: (val) => val === 1 ? "active" : "inactive",
};

/**
 * Event: when the period (days) selection is changed for any chart with class 'echart-period'
 */
$(document).ready(function () {
    // Initialize charts for all elements with class 'echart'
    $('.echart').each(function() {
        const id = $(this).attr('id');
        const type = $(this).attr('type');
        const autoUpdate = $(this).attr('autoupdate') || true;
        const autoUpdateInterval = $(this).attr('interval') || 15000;
        const days = $(this).attr('days') || 1; // Default days based on screen size
        const generate = $(this).attr('generate') !== undefined; // Check if 'generate' attribute is present

        // Initialize charts that have the 'generate' attribute to avoid unnecessary instances
        if (id && type && generate) {
            new EChart(type, id, autoUpdate, autoUpdateInterval, days);
        }
    });

    // Listen for changes on any select element with class 'echart-period'
    $('select.echart-period').on('change', function() {
        // Get chart Id
        const chartId = $(this).attr('chart-id');
        // Get selected value - convert to number to match internal storage
        const days = Number($(this).val());

        // Get chart type from 'type' or 'chart-type' attribute, default to 'line' if not specified
        const type = $('#' + chartId).attr('type') || $('#' + chartId).attr('chart-type') || 'line';

        // Destroy and recreate chart with new days value (pass as number)
        EChart.recreate(type, chartId, true, 15000, days);
    });

    // Listen for changes on any select element with class 'echart-range'
    // TODO: ranges
    // $('select.echart-range').on('change', function() {
    //     // Get chart Id
    //     const chartId = $(this).attr('chart-id');

    //     // Get selected value - convert to number to match internal storage
    //     const range = Number($(this).val());

    //     // Get chart type from 'type' or 'chart-type' attribute, default to 'line' if not specified
    //     const type = $('#' + chartId).attr('type') || $('#' + chartId).attr('chart-type') || 'line';

    //     // Destroy and recreate chart with new days value (pass as number)
    //     EChart.recreate(type, chartId, true, 15000, days);
    // });
});
