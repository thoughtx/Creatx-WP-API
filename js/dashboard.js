jQuery(document).ready(function ($) {
    'use strict';
    // Knob
    $('.dial-success').knob({
        readOnly: true,
        width: '70px',
        bgColor: '#E7E9EE',
        fgColor: '#259CAB',
        inputColor: '#262B36'
    });

    $('.dial-danger').knob({
        readOnly: true,
        width: '70px',
        bgColor: '#E7E9EE',
        fgColor: '#259CAB',
        inputColor: '#262B36'
    });

    $('.dial-info').knob({
        readOnly: true,
        width: '70px',
        bgColor: '#259CAB',
        fgColor: '#fff',
        inputColor: '#fff'
    });

    $('.dial-warning').knob({
        readOnly: true,
        width: '70px',
        bgColor: '#259CAB',
        fgColor: '#fff',
        inputColor: '#fff'
    });
    
    jQuery('.metaseo_tool').qtip({
        content: {
            attr: 'alt'
        },
        position: {
            my: 'bottom left',
            at: 'bottom left'
        },
        style: {
            tip: {
                corner: true,
            },
            classes: 'metaseo-qtip qtip-rounded metaseo-qtip-dashboard'
        },
        show: 'hover',
        hide: {
            fixed: true,
            delay: 10
        }

    });  
    
    jQuery('.metaseo_tool-000').qtip({
        content: {
            attr: 'alt'
        },
        position: {
            my: 'bottom left',
            at: 'bottom left'
        },
        style: {
            tip: {
                corner: true,
            },
            classes: 'metaseo-qtip metaseo-qtip-000 qtip-rounded metaseo-qtip-dashboard'
        },
        show: 'hover',
        hide: {
            fixed: true,
            delay: 10
        }

    });  

});