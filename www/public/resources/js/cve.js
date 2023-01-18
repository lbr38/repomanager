/**
 *  Event: click on 'Previous' button
 */
// $(document).on('click','#cve-list-prev',function () {
//     currentIndex = getCookie('cveStartIndex');

//     if (currentIndex != 0) {
//         newIndex = Number(currentIndex) - Number(100);
//     }

//     document.cookie = "cveStartIndex=" + newIndex + "; Secure";

//     reloadContentById('cves-div');
// });

// /**
//  *  Event: click on 'Next' button
//  */
// $(document).on('click','#cve-list-next',function () {
//     var currentPage = $('#current-page').attr('page');
//     var newPage = Number(currentPage) + Number(1);
//     var newIndex = Number(newPage) * Number(100);

//     $('#current-page').html('<b>' + newPage + '</b> ..');
//     $('#current-page').attr('page', newPage);

//     document.cookie = "cveStartIndex=" + newIndex + "; Secure; expires=Thu, 01 Jan 1970 00:00:00 UTC";

//     getCvePage(newPage);
//     // reloadContentById('cves-div');
// });

/**
 *  Search in CVE table
 */
// function searchCve()
// {
//     /**
//      *  If input is empty, exit
//      */
//     if (!$("#search-cve").val()) {
//         /**
//          *  Print all before exit
//          */
//         $('#cve-table').find('tr').show();

//         return;
//     }

//     /**
//      *  Get search input then convert it to uppercase (to ignore case)
//      */
//     search = $("#search-cve").val().toUpperCase();

//     /**
//      *  Hide all tr, only those whose match the research will be printed
//      */
//     $('#cve-table').find('tr:not(.first-row)').hide();

//     /**
//      *  L'utilisation de filtre peut laisser des espaces blancs
//      *  Suppression de tous les espaces blancs de la recherche globale
//      */
//     search = search.replaceAll(' ', '');

//     tr = $("#cve-table").find('tr:not(.first-row)');

//     tr.show().filter(function() {
//         var text = $(this).text().replace(/\s+/g, ' ').toUpperCase();
//         return !~text.indexOf(search);
//     }).hide();
// }
