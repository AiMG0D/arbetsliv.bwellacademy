@extends('user-layout-without-panel')

@section('page-header')
Välkommen {{ $user->full_name() }}
@stop

@section('content')
<div class="container">
    <div class="filters" style="background-color: white;">
        <select class="dropdown" style="background-color: white;" id="sectionDropdown">
            <option value="">
                Välj Avdelning
            </option>
            <?php foreach ($sections as $iterSection): ?>
                <option value="<?= $iterSection->unit_id . "." . $iterSection->id ?>" <?= $iterSection->id == optional($section ?? null)->id ? 'selected' : '' ?>>
                    <?= $iterSection->full_name() ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select class="dropdown" style="background-color: white;" id="genderDropdown">
            <option value="">
                Välj kön
            </option>
            <option value="1">Man</option>
            <option value="2">Kvinna</option>
        </select>

    </div>
    <div class="charts-container" style="margin-bottom: 20px;">
        <div class="chart-card" style="display: flex; align-items: center; justify-content: space-between;">
            <div style="width: 30%;">
                <h3 class="chart-title">Anställda</h3>
                <canvas id="donutChart"></canvas>
            </div>
            <div style="width: 60%;">
                <h3 class="chart-title ">Användare efter kategori</h3>
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>


    <div class="charts-container" style="margin-bottom: 20px;">
        <div class="chart-card" style="display: flex; align-items: center; justify-content: space-between;">
            <div style="width: 100%;">
                <h3 class="chart-title">Fysisk kapacitet - tester och frågor</h3>

                <div id="physicalStackedChart"></div>

            </div>

        </div>
    </div>


    <div class="charts-container" style="margin-bottom: 20px;">
        <div class="chart-card" style="display: flex; align-items: center; justify-content: space-between;">
            <div style="width: 100%;">
                <h3 class="chart-title">Upplevd hälsa</h3>

                <div id="wellbeingChart"></div>

            </div>

        </div>
    </div>

    <div class="charts-container" style="margin-bottom: 20px;">
        <div class="chart-card" style="display: flex; align-items: center; justify-content: space-between;">
            <div style="width: 100%;">
                <h3 class="chart-title">Beroendeområden</h3>

                <div id="antChart"></div>

            </div>

        </div>
    </div>


    <div class="charts-container" style="margin-bottom: 20px;">
        <div class="chart-card" style="display: flex; align-items: center; justify-content: space-between;">
            <div style="width: 100%;">
                <h3 class="chart-title">Mat och energi</h3>

                <div id="energyChart"></div>

            </div>

        </div>
    </div>

    <div class="charts-container" style="margin-bottom: 20px;">
        <div class="chart-card" style="display: flex; align-items: center; justify-content: space-between;">
            <div style="width: 100%;">
                <h3 class="chart-title">Livet på fritiden</h3>

                <div id="freetimeChart"></div>

            </div>

        </div>
    </div>

    <div class="charts-container" style="margin-bottom: 20px;">
        <div class="chart-card" style="display: flex; align-items: center; justify-content: space-between;">
            <div style="width: 100%;">
                <h3 class="chart-title">Mitt arbete</h3>

                <div id="workChart"></div>

            </div>

        </div>
    </div>

    <div class="charts-container" style="margin-bottom: 20px;">
        <div class="chart-card" style="display: flex; align-items: center; justify-content: space-between;">
            <div style="width: 100%;">
                <h3 class="chart-title">Känsla av sammanhang (KASAM)</h3>

                <div id="kasamChart"></div>

            </div>

        </div>
    </div>

    <div class="charts-container" style="margin-bottom: 20px;">
        <div class="chart-card" style="display: flex; align-items: center; justify-content: space-between;">
            <div style="width: 100%;">
                <h3 class="chart-title">Återkoppling</h3>

                <div id="feedbackChart"></div>

            </div>

        </div>
    </div>




</div>

<style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        font-family: Arial, sans-serif;
    }

    .filters {
        display: flex;
        justify-content: flex-start;
        gap: 15px;
        margin-bottom: 20px;
    }

    .dropdown {
        padding: 10px 15px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 14px;
        box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
    }

    .charts-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: flex-start;
    }

    .chart-card {
        flex: 1 1 calc(50% - 20px);
        background: #ffffff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        text-align: left;
    }

    .chart-title {
        font-size: 18px;
        font-weight: bold;
        color: #3276fb;
        margin-bottom: 15px;
    }

    .chart-subtitle {
        font-size: 14px;
        color: #6B7280;
        margin-top: 10px;
    }

    @media (max-width: 768px) {
        .charts-container {
            flex-direction: column;
        }

        .chart-card {
            flex: 1 1 100%;
        }
    }

    .checkbox-container {
        display: flex;
        gap: 15px;
        justify-content: center;
        padding: 20px;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        font-size: 16px;
        cursor: pointer;
    }

    .checkbox-label input {
        margin-right: 5px;
        width: 18px;
        height: 18px;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    let chartInstances = [];
    let gender = null
    let sectionId = null

    function fetchDataAndRenderCharts() {

        chartInstances.forEach(chart => chart.destroy());
        chartInstances = [];

        $.ajax("/statistics/filter/set", {
            dataType: "json",
            method: "post",
            data: {
                section: sectionId,
                sex: gender ? gender : ''
            }, // Send data only if a section is selected
            success: function(response) {
                console.log(response);
                if (!response || Object.keys(response).length === 0) {
                    // Clear the chart area and show a message
                    document.querySelector('.charts-container').innerHTML = `
                    <div class="empty">
                        @if (App::isLocale('sv'))
                        <h2>Det här urvalet har inga {{ config('fms.type') == 'work' ? 'anställda' : 'elever' }}</h2>
                        <h4>Använd filtreringsmenyn för att välja ett annat urval.</h4>
                        @else
                        <h2>This selection does not have any {{ config('fms.type') == 'work' ? 'employees' : 'students' }}</h2>
                        <h4>Use the filter menu to change selection.</h4>
                        @endif
                    </div>
                `;
                    return;
                }

                // Reset the charts container to its original state
                document.querySelector('.charts-container').innerHTML = `
                <div class="chart-card" style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="width: 30%;">
                        <h3 class="chart-title">Anställda</h3>
                        <canvas id="donutChart"></canvas>
                    </div>
                    <div style="width: 60%;">
                        <h3 class="chart-title ">Användare efter kategori</h3>
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            `;




                numMen = response.numMen ?? 0;
                numWomen = response.numWomen ?? 0;

                const donutCtx = document.getElementById('donutChart').getContext('2d');
                const donutChart = new Chart(donutCtx, {
                    type: 'doughnut',
                    data: {
                        labels: [`Male (n=${numMen})`, `Female (n=${numWomen})`],
                        datasets: [{
                            data: [numMen, numWomen],
                            backgroundColor: ['#3276fb', '#f75895'],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: '#3276fb',
                                    font: {
                                        size: 14
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(2);
                                        return ` ${percentage}%`;
                                    }
                                }
                            }
                        }
                    }
                });
                chartInstances.push(donutChart);

                riskMen = response.riskGroupMen?.risk ?? 0;
                friskMen = response.riskGroupMen?.healthy ?? 0;
                warningMen = response.riskGroupMen?.warning ?? 0;

                riskWomen = response.riskGroupWomen?.risk ?? 0;
                friskWomen = response.riskGroupWomen?.healthy ?? 0;
                warningWomen = response.riskGroupWomen?.warning ?? 0;

                const barCtx = document.getElementById('barChart').getContext('2d');
                const barChart = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Risk', 'Frisk', 'Warning'],
                        datasets: [{
                                label: 'Male',
                                data: [riskMen, friskMen, warningMen],
                                backgroundColor: '#3276fb',
                                borderRadius: 5,
                            },
                            {
                                label: 'Female',
                                data: [riskWomen, friskWomen, warningWomen],
                                backgroundColor: '#f75895',
                                borderRadius: 5,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: '#3276fb',
                                    font: {
                                        size: 14
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    grid: {
                                        color: '#e5e7eb'
                                    },
                                    ticks: {
                                        stepSize: 20
                                    }
                                }
                            }
                        }
                    }
                });

                chartInstances.push(barChart);


                createStackedColumnChart('physicalStackedChart', response.mappedLabels.physical ?? {}, response.mappedValues.physical ?? {});
                createStackedColumnChart('wellbeingChart', response.mappedLabels.wellbeing ?? {}, response.mappedValues.wellbeing ?? {});
                createStackedColumnChart('antChart', response.mappedLabels.ant ?? {}, response.mappedValues.ant ?? {});
                createStackedColumnChart('energyChart', response.mappedLabels.energy ?? {}, response.mappedValues.energy ?? {});
                createStackedColumnChart('freetimeChart', response.mappedLabels.freetime ?? {}, response.mappedValues.freetime ?? {});
                createStackedColumnChart('workChart', response.mappedLabels.work ?? {}, response.mappedValues.work ?? {});

                const vals = response.mappedValues.kasam; // [4, 1, 0]

                // 2) Swap index 0 and index 1
                [vals.kasam[0], vals.kasam[1]] = [vals.kasam[1], vals.kasam[0]];

                // 3) Now vals is [1, 4, 0]
                console.log("Kasam", vals);

                // 4) Draw your chart
                createColumnChart(
                    'kasamChart',
                    response.mappedLabels.kasam, // labels stay in whatever order you’ve set up
                    vals
                );
                // createChart('physicalChart',  response.mappedLabels.physical ?? {},  response.mappedValues.physical ?? {});
                //createChart('wellbeingChart',  response.mappedLabels.wellbeing ?? {}, response.mappedValues.wellbeing ?? {});
                // createChart('antChart',  response.mappedLabels.ant ?? {}, response.mappedValues.ant ?? {});
                // createChart('energyChart', response.mappedLabels.energy ?? {}, response.mappedValues.energy ?? {});
                //  createChart('freetimeChart', response.mappedLabels.freetime ?? {}, response.mappedValues.freetime ?? {});
                //  createChart('workChart', response.mappedLabels.work ?? {}, response.mappedValues.work ?? {});
                //  createChart('kasamChart', response.mappedLabels.kasam ?? {}, response.mappedValues.kasam ?? {});

            }
        });



        $.ajax("/statistics/chart", {
            dataType: "json",
            method: "get",
            data: {
                section: sectionId,
                sex: gender ? gender : ''
            },
            success: function(response) {

                // Mapping labels
                const labelMapping = {
                    agree: 'Agree',
                    neutral: 'Neutral',
                    disagree: 'Disagree'
                };

                // Mapping values based on response
                const valueMapping = {
                    agree: [response.count_one, 0, 0],
                    neutral: [response.count_zero, 0, 0],
                    disagree: [response.count_negative_one, 0, 0]
                };

                // Create the chart using the function
                createFeedbackChart(response);
            }
        });

        function createStackedColumnChart(chartId, labelMapping, valueMapping) {
            // Extract labels and values dynamically based on the mapping
            const labels = Object.values(labelMapping);
            const values = labels.map(label => {
                const key = Object.keys(labelMapping).find(k => labelMapping[k] === label);
                return valueMapping[key] || [0, 0, 0]; // Default to [0, 0, 0] if no value is found
            });
            const colors = ['#00FF00', '#FF0000', '#0000FF']; // Red, Green, Blue
            // Prepare dataset for ApexCharts
            const series = [

                {
                    name: 'Frisk',
                    data: values.map(v => v[0]) // Frisk values
                },
                {
                    name: 'Risk',
                    data: values.map(v => v[1]) // Risk values
                }
            ];

            // ApexCharts configuration
            const options = {
                series: series,
                colors: colors, // Apply custom colors
                chart: {
                    type: 'bar',
                    height: 450,
                    stacked: true,
                    toolbar: {
                        show: true
                    },
                    zoom: {
                        enabled: true
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        legend: {
                            position: 'bottom',
                            offsetX: -10,
                            offsetY: 0
                        }
                    }
                }],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        borderRadius: 10,
                        borderRadiusApplication: 'end', // 'around', 'end'
                        borderRadiusWhenStacked: 'last', // 'all', 'last'
                        dataLabels: {
                            total: {
                                enabled: true,
                                style: {
                                    fontSize: '13px',
                                    fontWeight: 900
                                }
                            }
                        }
                    }
                },
                xaxis: {
                    categories: labels, // Use dynamic labels
                    labels: {
                        formatter: function(value) {
                            return value; // Display the label as is
                        }
                    }
                },
                legend: {
                    position: 'right',
                    offsetY: 40
                },
                fill: {
                    opacity: 1
                }
            };

            // Render the chart
            const chart = new ApexCharts(document.querySelector(`#${chartId}`), options);
            chart.render();
        }

        function createColumnChart(chartId, labelMapping, valueMapping) {
            // Extract labels and corresponding values dynamically
            const labels = Object.values(labelMapping);
            const values = labels.map(label => {
                const key = Object.keys(labelMapping).find(k => labelMapping[k] === label);
                return valueMapping[key] || [0, 0, 0]; // Default to [0, 0, 0] if no value is found
            });

            const colors = ['#FF0000', '#00FF00']; // Red for Risk, Green for Frisk

            // Prepare dataset for ApexCharts
            const series = [{
                    name: 'Risk',
                    data: values.map(v => v[0]) // Risk values
                },
                {
                    name: 'Frisk',
                    data: values.map(v => v[1]) // Frisk values
                }
            ];

            // ApexCharts configuration
            const options = {
                series: series,
                colors: colors, // Apply custom colors
                chart: {
                    type: 'bar',
                    height: 450,
                    stacked: false, // Ensure bars are NOT stacked
                    toolbar: {
                        show: true
                    },
                    zoom: {
                        enabled: true
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        legend: {
                            position: 'bottom',
                            offsetX: -10,
                            offsetY: 0
                        }
                    }
                }],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        borderRadius: 8,
                        columnWidth: '50%', // Adjust bar width
                        dataLabels: {
                            position: 'top', // Ensure labels are readable
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val.toFixed(2); // Display formatted values
                    },
                    offsetY: -10,
                    style: {
                        fontSize: '12px',
                        colors: ['#333']
                    }
                },
                xaxis: {
                    categories: labels, // Use dynamic labels
                    labels: {
                        rotate: -45, // Improve readability for long labels
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                legend: {
                    position: 'right',
                    offsetY: 40
                },
                fill: {
                    opacity: 1
                }
            };

            // Render the chart
            const chart = new ApexCharts(document.querySelector(`#${chartId}`), options);
            chart.render();
        }

        function createFeedbackChart(response) {
            console.log('Feedback chart data:', response);

            // First, create the main container with a header
            const chartContainer = document.querySelector("#feedbackChart");
            chartContainer.innerHTML = `
        <div class="feedback-overall-chart" style="margin-bottom: 30px;">
            <h4 style="margin-bottom: 15px; font-size: 16px; color: #666;">Sammanfattning av återkoppling</h4>
            <div id="overallFeedbackChart"></div>
        </div>
        <div class="feedback-questions-container">
            <h4 style="margin: 15px 0; font-size: 16px; color: #666;">Svar per fråga</h4>
            <div id="questionsChartsContainer"></div>
        </div>
    `;

            // Wait for DOM to be ready, then create the overall chart
            setTimeout(() => {
                // Create the overall feedback chart
                const labels = ['Håller helt med', 'Håller delvis med', 'Håller inte med'];
                const values = [response.count_one, response.count_zero, response.count_negative_one];

                const overallOptions = {
                    chart: {
                        type: 'bar',
                        height: 300,
                        toolbar: {
                            show: false
                        }
                    },
                    series: [{
                        name: 'Totalt antal svar',
                        data: values
                    }],
                    xaxis: {
                        categories: labels,
                        labels: {
                            style: {
                                fontSize: '12px'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                fontSize: '12px'
                            }
                        }
                    },
                    colors: ['#10B981', '#3B82F6', '#EF4444'], // Green, Blue, Red
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '60%',
                            distributed: true, // Each bar gets its own color
                            borderRadius: 10
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontSize: '14px',
                            fontWeight: 'bold',
                            colors: ['#fff']
                        }
                    },
                    legend: {
                        show: false // Hide legend since we're using distributed colors
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                const total = response.count_one + response.count_zero + response.count_negative_one;
                                const percentage = ((val / total) * 100).toFixed(1);
                                return `${val} svar (${percentage}%)`;
                            }
                        }
                    }
                };

                const overallChart = new ApexCharts(document.querySelector("#overallFeedbackChart"), overallOptions);
                overallChart.render();
                console.log('Overall feedback chart rendered successfully');

            }, 100);

            // Create individual question charts
            if (response.questions && Object.keys(response.questions).length > 0) {
                const questionsContainer = document.querySelector("#questionsChartsContainer");

                // Filter out questions with no responses
                const validQuestions = Object.keys(response.questions).filter(questionName => {
                    const question = response.questions[questionName];
                    const totalResponses = question.count_one + question.count_zero + question.count_negative_one;
                    return totalResponses > 0;
                });

                console.log(`Creating charts for ${validQuestions.length} valid questions:`, validQuestions);

                // Wait for DOM, then create grid container
                setTimeout(() => {
                    // Create a grid for question charts
                    const gridContainer = document.createElement('div');
                    gridContainer.style.display = 'grid';
                    gridContainer.style.gridTemplateColumns = 'repeat(auto-fit, minmax(400px, 1fr))';
                    gridContainer.style.gap = '20px';
                    gridContainer.style.marginTop = '20px';
                    questionsContainer.appendChild(gridContainer);

                    // Create each question chart
                    validQuestions.forEach((questionName, index) => {
                        const question = response.questions[questionName];

                        console.log(`Creating chart ${index + 1} for question:`, questionName, question);

                        // Create chart container
                        const questionChartContainer = document.createElement('div');
                        questionChartContainer.className = 'question-chart-card';
                        questionChartContainer.style.background = '#f9f9f9';
                        questionChartContainer.style.borderRadius = '10px';
                        questionChartContainer.style.padding = '15px';
                        questionChartContainer.style.boxShadow = '0 2px 5px rgba(0,0,0,0.05)';

                        // Add chart title (question text)
                        const questionTitle = document.createElement('h5');
                        questionTitle.textContent = question.text;
                        questionTitle.style.fontSize = '14px';
                        questionTitle.style.marginBottom = '15px';
                        questionTitle.style.color = '#333';
                        questionTitle.style.lineHeight = '1.4';
                        questionTitle.style.wordWrap = 'break-word';
                        questionChartContainer.appendChild(questionTitle);

                        // Create chart div with unique ID
                        const chartDiv = document.createElement('div');
                        const chartId = `question-chart-${questionName.replace(/[^a-zA-Z0-9]/g, '-')}-${index}`;
                        chartDiv.id = chartId;
                        questionChartContainer.appendChild(chartDiv);

                        // Add to grid
                        gridContainer.appendChild(questionChartContainer);

                        // Create chart with delay
                        setTimeout(() => {
                            const chartElement = document.querySelector(`#${chartId}`);
                            if (!chartElement) {
                                console.error(`Chart element not found: ${chartId}`);
                                return;
                            }

                            const questionValues = [question.count_one, question.count_zero, question.count_negative_one];
                            const totalResponses = questionValues.reduce((a, b) => a + b, 0);
                            const labels = ['Håller helt med', 'Håller delvis med', 'Håller inte med'];

                            const questionOptions = {
                                chart: {
                                    type: 'bar',
                                    height: 250,
                                    toolbar: {
                                        show: false
                                    }
                                },
                                series: [{
                                    name: 'Antal svar',
                                    data: questionValues
                                }],
                                xaxis: {
                                    categories: labels,
                                    labels: {
                                        style: {
                                            fontSize: '11px'
                                        },
                                        rotate: -45
                                    }
                                },
                                yaxis: {
                                    labels: {
                                        style: {
                                            fontSize: '11px'
                                        }
                                    }
                                },
                                colors: ['#10B981', '#3B82F6', '#EF4444'], // Same colors as overall chart
                                plotOptions: {
                                    bar: {
                                        horizontal: false,
                                        columnWidth: '70%',
                                        distributed: true,
                                        borderRadius: 8
                                    }
                                },
                                dataLabels: {
                                    enabled: true,
                                    style: {
                                        fontSize: '12px',
                                        fontWeight: 'bold',
                                        colors: ['#fff']
                                    }
                                },
                                legend: {
                                    show: false
                                },
                                tooltip: {
                                    y: {
                                        formatter: function(val) {
                                            const percentage = ((val / totalResponses) * 100).toFixed(1);
                                            return `${val} svar (${percentage}%)`;
                                        }
                                    }
                                }
                            };

                            try {
                                const questionChart = new ApexCharts(chartElement, questionOptions);
                                questionChart.render();
                                console.log(`Successfully rendered chart ${index + 1} for: ${questionName}`);
                            } catch (error) {
                                console.error(`Error rendering chart for ${questionName}:`, error);
                            }
                        }, 200 * (index + 1)); // Stagger the chart creation
                    });

                    // Add summary info after all charts
                    setTimeout(() => {
                        const summaryDiv = document.createElement('div');
                        summaryDiv.style.marginTop = '30px';
                        summaryDiv.style.padding = '20px';
                        summaryDiv.style.background = '#f0f9ff';
                        summaryDiv.style.borderRadius = '10px';
                        summaryDiv.style.borderLeft = '4px solid #3B82F6';
                        const totalResponses = response.count_one + response.count_zero + response.count_negative_one;
                        summaryDiv.innerHTML = `
                    <h5 style="margin: 0 0 10px 0; color: #1e40af; font-size: 16px;">📊 Sammanfattning</h5>
                    <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                        <div style="background: white; padding: 10px; border-radius: 8px; min-width: 120px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #10B981;">${response.count_one}</div>
                            <div style="font-size: 12px; color: #666;">Håller helt med</div>
                        </div>
                        <div style="background: white; padding: 10px; border-radius: 8px; min-width: 120px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #3B82F6;">${response.count_zero}</div>
                            <div style="font-size: 12px; color: #666;">Håller delvis med</div>
                        </div>
                        <div style="background: white; padding: 10px; border-radius: 8px; min-width: 120px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #EF4444;">${response.count_negative_one}</div>
                            <div style="font-size: 12px; color: #666;">Håller inte med</div>
                        </div>
                        <div style="background: white; padding: 10px; border-radius: 8px; min-width: 120px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #333;">${totalResponses}</div>
                            <div style="font-size: 12px; color: #666;">Totalt svar</div>
                        </div>
                    </div>
                    <p style="margin: 15px 0 0 0; font-size: 14px; color: #666;">
                        <strong>${validQuestions.length}</strong> frågor visas av ${Object.keys(response.questions).length} totalt
                    </p>
                `;
                        questionsContainer.appendChild(summaryDiv);
                    }, 1000);

                }, 150);
            } else {
                console.warn('No questions data available or questions object is empty');
            }
        }
        // Function to create a chart
        function createChart(chartId, labelMapping, valueMapping) {
            const ctx = document.getElementById(chartId).getContext('2d');
            const labels = Object.values(labelMapping);
            const values = labels.map(label => {
                const key = Object.keys(labelMapping).find(k => labelMapping[k] === label);
                return valueMapping[key] || [0, 0, 0]; // Default to [0, 0, 0] if no value is found
            });
            // Calculate the maximum value in the dataset
            const maxValue = Math.max(...values.flat());

            // Add a margin to the maximum value (e.g., 20% of the max value)
            const margin = maxValue * 0.1;
            const suggestedMax = maxValue + margin;

            chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Risk',
                            data: values.map(v => v[0]),
                            backgroundColor: '#eb4034',
                        },
                        {
                            label: 'Frisk',
                            data: values.map(v => v[1]),
                            backgroundColor: '#7FE563',
                        },
                        {
                            label: 'Varning',
                            data: values.map(v => v[2]),
                            backgroundColor: '#333333',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(2);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: true
                            }
                        },
                        y: {
                            grid: {
                                color: '#e5e7eb'
                            },
                            stepSize: 1, // Step size for sub-ranges
                            callback: function(value) {
                                // Display intermediate ticks
                                return value;
                            },
                            suggestedMax: suggestedMax // Add margin to the y-axis

                        }
                    }
                }
            });
            chartInstances.push(chart);

        }
    }

    window.addEventListener('load', function() {
        sectionId = document.getElementById('sectionDropdown').value
        fetchDataAndRenderCharts();
    });

    document.getElementById('sectionDropdown').addEventListener('change', function() {
        const selectedSectionId = this.value;
        sectionId = this.value
        fetchDataAndRenderCharts();
    });
    document.getElementById('genderDropdown').addEventListener('change', function() {
        const selectedSectionId = this.value;
        gender = this.value
        fetchDataAndRenderCharts();
    });
</script>
@stop