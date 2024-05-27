$(function() {

    // Total Sales - Bar
    $('#sales-compositebar').sparkline([4, 6, 7, 7, 4, 3, 2, 3, 1, 4, 6, 5, 9, 4, 6, 7, 7, 4, 6, 5, 9], {
        type: 'bar',
        barColor: '#F6CAFD',
        height: '25',
        width: '100%',
        barWidth: '7',
        barSpacing: 4
    });
    //Total Sales - Line
    $('#sales-compositebar').sparkline([4, 1, 5, 7, 9, 9, 8, 8, 4, 2, 5, 6, 7], {
        composite: true,
        type: 'line',
        width: '100%',
        lineWidth: 2,
        lineColor: '#fff3e0',
        fillColor: 'rgba(255, 82, 82, 0.25)',
        highlightSpotColor: '#fff3e0',
        highlightLineColor: '#fff3e0',
        minSpotColor: '#00bcd4',
        maxSpotColor: '#00e676',
        spotColor: '#fff3e0',
        spotRadius: 4
    });
})