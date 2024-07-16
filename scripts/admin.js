//login = $('#login_holder').text().trim();
//level = $('#level_holder').text().trim();

let sortOrder = {};
let columnSearches = {};
let dbTable = isCookieSet('dbTable') ? getCookie('dbTable') : 'orders'; //setCookie('dbTable', 'clients', 365);
$('.mainMenu a[data-id="'+dbTable+'"]').addClass('activated'); // Mark active tab
let company = isCookieSet('company') ? getCookie('company') : 'Hydro-Dyna'; //setCookie('dbTable', 'clients', 365);
$('.logo').attr('src', 'images/' + company + '.png'); // Mark active tab
$('#company').val(company);
historyStatus = '';
archived = '';
sortByDate = '';
blockCounter = 1;

currentDateX = new Date();
month = currentDateX.getMonth() + 1;
year = currentDateX.getFullYear();

// Insert current date into date fields
function currentDateTime(format = 'datetime') {
    let options = {
        timeZone: 'Europe/Warsaw',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        weekday: 'short',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    };
    let formatter = new Intl.DateTimeFormat('pl-PL', options);
    let parts = formatter.formatToParts(new Date());
    let dateParts = {};
    parts.forEach(part => {
        dateParts[part.type] = part.value;
    });
    const dayMap = {
        "pon.": 'Pn',
        "wt.": 'Wt',
        "śr.": 'Śr',
        "czw.": 'Cz',
        "pt.": 'Pt',
        "sob.": 'Sb',
        "niedz.": 'Nd'
    };
    let weekday = dateParts.weekday;
    let shortDay = dayMap[weekday] || weekday;
    if (format == 'datetime') {
        // currentDateTime
        dt = `${dateParts.year}-${dateParts.month}-${dateParts.day}T${dateParts.hour}:${dateParts.minute}`;
    } else if (format == 'dateTimeSec') {
        dt = `${shortDay}. ${dateParts.day}-${dateParts.month}-${dateParts.year} ${dateParts.hour}:${dateParts.minute}:${dateParts.second}`;
    } else {
        // currentDate
        dt = `${dateParts.year}-${dateParts.month}-${dateParts.day}`;
    }
    return dt;
}

function updateDateTime() {
    const formattedDateTime = currentDateTime('dateTimeSec');
    document.getElementById('dateTime').textContent = ' ' + formattedDateTime;
}
updateDateTime();
setInterval(updateDateTime, 1000);

$(document).ready(function() {

    if (dbTable == 'orders') {
        $('#ordersArchived').show();
        $('.findMe').show();
        $('#infoBar').hide();
    } else if (dbTable == 'panel') {
        $('#ordersArchived').hide();
        $('.findMe').hide();
        $('#infoBar').show();
        updateDatabase('others', 'complaints', '', 'select', 'check');
    } else {
        $('#ordersArchived').hide();
        $('.findMe').show();
        $('#infoBar').hide();
    }

    if (dbTable == 'calendar') {
        $('.findMe').hide();
        if (window.refreshOrders) clearInterval(window.refreshOrders);
        $('#flexContainer').hide();
        $('.orderContainers').empty(); // Reset orders list
        $('#columnHover a, #addNew, #changeCompany').hide(); //.removeClass('dimmed');
        $('#searchBoxes').hide();
        $('#calendarMenu').show();
        // Hide table
        $('#dropdownContent, #columnSearch, #recordsTable tbody, #columnNames, #recordForm').empty(); // Reset db tables
        fetchCalendar(month, year);
        fetchStatistics();
    } else if (dbTable !== 'panel') {
        $('#flexContainer').hide();
        $('.orderContainers').empty(); // Reset orders list
        $('#columnHover a, #addNew, #changeCompany').show(); //.removeClass('dimmed');
        $('#searchBoxes').hide();
        $('#calendarMenu').hide();
        $('#calendarContainer').empty();
        //$('body').css('margin-bottom', '0');
        fetchRecords(dbTable, company, archived, sortByDate);
        fetchStatistics();
    } else {
        window.refreshOrders = setInterval(function() {fetchOrders('auto');}, 3 * 1000); // Refresh every 60 seconds
        $('#flexContainer').show();
        $('#dropdownContent, #columnSearch, #recordsTable tbody, #columnNames').empty(); // Reset db tables
        //$('#dropdownContent, #columnSearch, #recordsTable tbody, #columnNames, #recordForm').empty(); // Reset db tables
        $('#columnHover a, #addNew, #changeCompany').hide(); //.addClass('dimmed');
        $('#searchBoxes').show();
        $('#calendarMenu').hide();
        $('#calendarContainer').empty();
        //$('body').css('margin-bottom', '31px');
        fetchOrders();
        fetchStatistics();
    }
    if (dbTable == 'clients') {
        $('#addNew').text('Dodaj Klienta');
    } else if (dbTable == 'orders' || dbTable == 'panel') {
        $('#addNew').text('Dodaj Zlecenie');
    } else if (dbTable == 'users') {
        $('#addNew').text('Dodaj Użytkownika');
    } else if (dbTable == 'companies') {
        $('#addNew').text('Dodaj Firmę');
    }

    // Make the columnNames element sticky at the top
    const columnNames = $('#columnNames');
    if (columnNames.length) {
        columnNames.css({
            position: 'sticky',
            top: '0',
            background: 'white',
            zIndex: 1
        });
    }

    // Switch to Panel
    $(document).on('click', '.switchBtn', function() {
        let id = $(this).data('id');

        $('.mainMenu a').removeClass('activated');
        $('#ordersPanel').addClass('activated');

        dbTable = 'panel';
        setCookie('dbTable', dbTable, 365);
        
        $('.tooltip').hide();
        $('#ordersArchived').hide();
        $('#flexContainer').show();
        $('#dropdownContent, #columnSearch, #recordsTable tbody, #columnNames, #recordForm').empty(); // Reset db tables
        $('#columnHover a, #addNew, #changeCompany').hide(); //.addClass('dimmed');
        $('#searchBoxes').show();

        window.refreshOrders = setInterval(function() {fetchOrders('auto');}, 3 * 1000); // Refresh every 60 seconds

        fetchOrders('', id);
        fetchStatistics();
    });

    // Switch to list
    $(document).on('click', '.backToList', function() {
        let id = $(this).data('id');

        // RESET
        $('#flexContainer').hide();
        $('.orderContainers').empty(); // Reset orders list
        $('#columnHover a, #addNew, #changeCompany').show(); //.removeClass('dimmed');
        $('#searchBoxes').hide();
        $('#calendarMenu').hide();
        $('#calendarContainer').empty();
        $('#infoBar').hide();
        $('.tooltip').hide(); // additional
        

        $('.mainMenu a').removeClass('activated');
        $('#ordersList').addClass('activated');

        dbTable = 'orders';
        setCookie('dbTable', dbTable, 365);

        $('.findMe').show();
        $('#ordersArchived').show();
        $('#flexContainer').hide();
        $('.orderContainers').empty(); // Reset orders list
        $('#columnHover a, #addNew, #changeCompany').show(); //.removeClass('dimmed');
        $('#searchBoxes').hide();

        if (window.refreshOrders) clearInterval(window.refreshOrders);

        resetSearch();
        fetchRecords(dbTable, company, archived, sortByDate, id, true);
        fetchStatistics();
    });

    // PANEL
    $(document).on('mouseenter', '.backToList', function() {
        $(this).css({
            'text-decoration': 'underline',
            'cursor': 'pointer'
        });
    });
    $(document).on('mouseleave', '.backToList', function() {
        $(this).css({
            'text-decoration': 'none',
            'cursor': 'default'
        });
    });

    $(document).on('click', '.findMe img', function() {
        //$('#columnSearch').slideToggle();
        if ($('#recordsTable').find('#columnSearch').is(':visible')) {
            $('#recordsTable').find('#columnSearch').hide();
        } else {
            $('#recordsTable').find('#columnSearch').show();
        }
    });

    $('.mainMenu').on('click', 'a[data-id]', function() {
        $('.mainMenu a').removeClass('activated');
        $(this).addClass('activated');

        dbTable = $(this).attr('data-id');
        setCookie('dbTable', dbTable, 365);
        
        if (dbTable == 'orders') {
            $('#ordersArchived').show();
            $('.findMe').show();
            $('#infoBar').hide();
        } else if (dbTable == 'panel') {
            $('#ordersArchived').hide();
            $('.findMe').hide();
            $('#infoBar').show();
            updateDatabase('others', 'complaints', '', 'select', 'check');
        } else {
            $('#ordersArchived').hide();
            $('.findMe').show();
            $('#infoBar').hide();
        }

        if (dbTable == 'calendar') {
            $('.findMe').hide();
            if (window.refreshOrders) clearInterval(window.refreshOrders);
            $('#flexContainer').hide();
            $('.orderContainers').empty(); // Reset orders list
            $('#columnHover a, #addNew, #changeCompany').hide(); //.removeClass('dimmed');
            $('#searchBoxes').hide();
            $('#calendarMenu').show();
            // Hide table
            $('#dropdownContent, #columnSearch, #recordsTable tbody, #columnNames, #recordForm').empty(); // Reset db tables
            fetchCalendar(month, year);
            fetchStatistics();
        } else if (dbTable !== 'panel') {
            $('#flexContainer').hide();
            $('.orderContainers').empty(); // Reset orders list
            $('#columnHover a, #addNew, #changeCompany').show(); //.removeClass('dimmed');
            $('#searchBoxes').hide();
            $('#calendarMenu').hide();
            $('#calendarContainer').empty();
            //$('body').css('margin-bottom', '0');
            fetchRecords(dbTable, company, archived, sortByDate);
            fetchStatistics();
            resetSearch(); // reset search
        } else {
            window.refreshOrders = setInterval(function() {fetchOrders('auto');}, 3 * 1000); // Refresh every 60 seconds
            $('#flexContainer').show();
            $('#dropdownContent, #columnSearch, #recordsTable tbody, #columnNames').empty(); // Reset db tables
            //$('#dropdownContent, #columnSearch, #recordsTable tbody, #columnNames, #recordForm').empty(); // Reset db tables
            $('#columnHover a, #addNew, #changeCompany').hide(); //.addClass('dimmed');
            $('#searchBoxes').show();
            $('#calendarMenu').hide();
            $('#calendarContainer').empty();
            //$('body').css('margin-bottom', '31px');
            fetchOrders();
            fetchStatistics();
        }

        if (dbTable == 'clients') {
            $('#addNew').text('Dodaj Klienta');
        } else if (dbTable == 'orders' || dbTable == 'panel') {
            $('#addNew').text('Dodaj Zlecenie');
        } else if (dbTable == 'users') {
            $('#addNew').text('Dodaj Użytkownika');
        } else if (dbTable == 'companies') {
            $('#addNew').text('Dodaj Firmę');
        }
    });

    $('#columnHover').on('mouseover', function() {
        if (dbTable !== 'panel' && dbTable !== 'calendar') {
            $('#dropdownContent').css('visibility','visible');
        } else {
            $('#dropdownContent').css('visibility','hidden');
        }
    });

    // Show labels on EDIT ADDNEW popup boxes
    // Event delegation: Attach event to the static parent, targeting dynamic elements
    $(document).on('focus', 'input[type="text"]', function() {
        var $input = $(this);
        var $label = $input.next('label'); // Assuming label follows input in the markup

        $label.css({
            'visibility': 'visible',
            'opacity': '1',
            'z-index': '100',
            'transform': 'translateY(-20px)'
        });
    }).on('blur', 'input[type="text"]', function() {
        var $input = $(this);
        var $label = $input.next('label');

        $label.css({
            'visibility': 'hidden',
            'opacity': '0',
            'z-index': '-1',
            'transform': 'translateY(0px)'
        });
    });

    $('#ordersArchived, #sortByDate').on('change', 'input[type="checkbox"][name="column"]', function() {
        if ($(this).is(':checked')) {
            sortByDate = 1;
        } else {
            sortByDate = 0;
        }
        saveCheckboxes(dbTable);
        fetchRecords(dbTable, company, archived, sortByDate);
    });

    // Listen for changes on any checkbox within the #columnToggles container
    $('#columnToggles').on('change', 'input[type="checkbox"][name="column"]', function() {
        let column = $(this).val();
        if ($(this).is(':checked')) {
            $('th[data-column="' + column + '"], td[data-column="' + column + '"]').show();
        } else {
            $('th[data-column="' + column + '"], td[data-column="' + column + '"]').hide();
        }
        saveCheckboxes(dbTable);
        adjustTableMargin();
    });

    $('#ordersArchived').on('change', 'input[type="checkbox"][name="archivedCheck"]', function() {
        if ($(this).is(':checked')) {
            archived = 1;
            $('tbody tr').hide();
            $('tbody tr.archived').show();
        } else {
            archived = '';
            $('tbody tr').show();
            $('tbody tr.archived').hide();
        }
        updateRowCount(dbTable);
        //fetchRecords(dbTable, company, archived, sortByDate);
    });

    $('#columnNames').on('click', '.th', function() {
        let index = $(this).index();
        let table = $(this).parents('table').eq(0);
        let rows = table.find('tbody tr').toArray().sort(comparer(index));
    
        // Toggle the sortOrder for the clicked column
        if (!sortOrder[index]) {
            sortOrder[index] = 'asc';
        } else {
            sortOrder[index] = sortOrder[index] === 'asc' ? 'desc' : 'asc';
        }
    
        // Reverse the rows if sortOrder is 'desc'
        if (sortOrder[index] === 'desc'){ 
            rows = rows.reverse();
        }
    
        // Append the sorted rows back to the tbody
        table.find('tbody').empty().append(rows);
    
        // Remove existing sort icons from all columns
        $('.th .sort-icon').remove();
    
        // Add the correct sort icon to the clicked column
        let sortIcon = sortOrder[index] === 'asc' ? '&#9650;' : '&#9660;'; // Up arrow for 'asc', down arrow for 'desc'
        $(this).append(`<span class="sort-icon">${sortIcon}</span>`);
    });

    $(document).on('click', '#addNew', function() {
        blockCounter = 1;

        $('.notification_bg_white').hide();
        $('#editTip').hide();
        $('.logo').attr('src', 'images/' + company + '.png');

        // RESET ALL
        $('.part_name:first').css({'max-width': '337px', 'margin-right': '5px'});
        $('#nip').removeClass('highlight_green highlight_red'); // Reset NIP
        $('#client_exists').text('');
        $('.interference_note_box').hide();
        $('.add_button').show();
        $('.duplicate').remove();
        $('input, select').removeClass('missed'); // Reset red fields

        $('#send_sms').prop('disabled', false);
        $('#send_sms_note').prop('disabled', false);
        $('#send_sms_note_box, #sms_counter, label[for="send_sms"]').css('opacity', '1');
        $('#send_sms_note_box, #sms_counter').hide();

        $('#individual').prop('checked', false);
        $('#nip').prop('disabled', false);
        $('#nip').attr('placeholder', 'NIP').css('font-weight', 'bold'); // .val(nip_val)
        $('#nip, label[for="nip"]').css('opacity', '1');

        if (dbTable !== 'panel' && dbTable !== 'calendar') {
            $('#id').val('');
            $('#recordForm')[0].reset(); // Reset the form to clear any previous inputs
            popupPosition();
            $('#overlay, #formPopup').fadeIn(); // Show the form
        }
        // Earlier was if else
        if (dbTable == 'orders') {
            $('#company').val(company);
            $('#history').val();

            // sms length
            if ($('#send_sms_note').length > 0) {
                let charCount = [...$('#send_sms_note').val()].reduce((acc, char) => acc + (/[ąćęłńóśźż]/.test(char) ? 2 : 1), 0);
                $('#sms_counter span').eq(1).text(160 - charCount);
            }
        }
        
        let dateField = $('#verification_date_start');
        if (!dateField.val()) {
            dateField.val(currentDateTime('date'));
        }
        dateField = $('#repair_date_start');
        if (!dateField.val()) {
            dateField.val(currentDateTime('date'));
        }
        dateField = $('#acceptance_date');
        if (!dateField.val()) {
            dateField.val(currentDateTime());
        }
        //console.log(currentDateTime());
        //console.log(currentDateTime('date'));

        gusData = null; // RESET - fix for a situation when order from client with NIP added and then from client without NIP
    });

    $('tbody').on('click', '.editBtn', function() {
        const id = $(this).data('id');
        fetchRecordDetails(id, dbTable); // Fetch details and populate the form
        $('html, body').animate({scrollTop: 0, scrollLeft: 0}, 800, function() {
            // option to display form after scroll
        });
    });

    //$('tbody').on('click', '.printBtn', function() {
    $(document).on('click', '.printBtn', function() {
        const id = $(this).data('id');
        const method = $(this).data('type');
        let single = pdf = null;
        if ($(this).parent().parent().hasClass('action-buttons')) {
            single = 'single';
        } else if ($(this).parent().find('p .create_pdfs').is(':checked')) {
            pdf = 'pdf';
        }
        printLabel(id, dbTable, method, company, single, pdf);
    });

    $('tbody').on('click', '.delBtn', function() {
        if (!$(this).parent().parent().parent().hasClass('deleted') && confirm("Potwierdź usunięcie")) {
            const id = $(this).data('id');
            deleteRecord(id, dbTable, 'delete', company); // Fetch details and populate the form
        }
    });

    $('tbody').on('click', '.desBtn', function() {
        if ($(this).parent().parent().parent().hasClass('deleted') && confirm("Potwierdź usunięcie (UWAGA: NIEODWRACALNE!)")) {
            const id = $(this).data('id');
            deleteRecord(id, dbTable, 'destroy', company); // Fetch details and populate the form
        }
    });

    $('tbody').on('click', '.arcBtn', function() {
        if (confirm("Potwierdź przeniesienie do archiwum")) {
           const id = $(this).data('id');
           deleteRecord(id, dbTable, 'archive', company); // Fetch details and populate the form
        }
    });

    $('tbody').on('click', '.resBtn', function() {
        if ($(this).parent().parent().parent().hasClass('deleted') && confirm("Potwierdź przywrócenie")) {
            const id = $(this).data('id');
            deleteRecord(id, dbTable, 'restore', company); // Fetch details and populate the form
        }
    });

    $('#recordForm').on('click', '#saveBtn', function(e) {
        e.preventDefault(); // Prevent the default form submission
        saveRecord(dbTable, company); // Save or update the record
    });

    $('#recordForm, #printForm').on('click', '.cancelBtn', function(e) {
        $('#formPopup, #printPopup').fadeOut(250);
        $('#overlay').fadeOut(250, function(){
            if (dbTable != 'calendar') {
                fetchRecords(dbTable, company, archived, sortByDate, '', false, 1); // Reload results in case status was changed and cancelled
                fetchOrders();
            }
        });
        // Clear timer()
    });

    $(document).on('keyup', '#searchBox2', function() {
        let searchTerm = $(this).val().toLowerCase();

        let tr;
        let rowText;
        if (archived == '1') {
            tr = 'tr.archived';
        } else {
            tr = 'tr:not(tr.archived)';
        }
    
        $('#recordsTable tbody ' + tr).each(function() {
            let row = $(this);
            let isMatch = false;
            
            // Concatenate all text from the row's cells
            rowText = row.find('td').text().toLowerCase();
            
            if (rowText.indexOf(searchTerm) !== -1) {
                isMatch = true;
            }
    
            // Toggle row visibility based on match status
            $(this).toggle(isMatch);
        });
        updateRowCount(dbTable);
    });

    $('#columnSearch').on('keyup', '.searchInput', function() {
        let searchIndex = $(this).parent().index();
        let searchTerm = $(this).val().toLowerCase();
        //if (searchTerm.length < 2) return;
        //console.log(searchIndex);
        
        // Update the search term for the current column in the global object
        columnSearches[searchIndex] = searchTerm;

        let tr;
        let rowText;
        if (archived == '1') {
            tr = 'tr.archived';
        } else {
            tr = 'tr:not(tr.archived)';
        }

        $('#recordsTable tbody ' + tr).each(function() {
            let isMatch = true;
            let row = $(this);
    
            // Check this row against all search terms in all columns
            Object.keys(columnSearches).forEach(function(index) {
                let columnSearchTerm = columnSearches[index].toLowerCase();
                if (columnSearchTerm !== "") {
                    if (searchIndex === 26) { // status
                        rowText = row.removeClass('hideMe').find('td').eq(index).find('select[name="status"] option:selected').text().toLowerCase();
                    } else {
                        rowText = row.removeClass('hideMe').find('td').eq(index).text().toLowerCase();
                    }
                    if (rowText.indexOf(columnSearchTerm) === -1) {
                        isMatch = false; // This row does not match the search criteria
                    }
                }
            });
    
            // Toggle row visibility based on match status
            $(this).toggle(isMatch);
        });
        updateRowCount(dbTable);
    });

    $(document).on('click', '#resetSearch', function() {
        resetSearch();
    });

    // Row highlighting functionality
    $('#recordsTable tbody').on('mouseenter', 'tr', function() {
        $(this).addClass('highlight').siblings().removeClass('highlight');
    }).on('mouseleave', 'tr', function() {
        $(this).removeClass('highlight');
    });

    // Create the 'Top' link and append it to the body
    let topLink = $('<a href="#" id="topLink">&#8593;</a>');
    $('body').append(topLink);
    topLink.hide();

    // Show the link when scrolling down
    $(window).scroll(function() {
        if ($(this).scrollTop() > 100) {
            topLink.fadeIn();
        } else {
            topLink.fadeOut();
        }
    });

    // Scroll smoothly to the top when the link is clicked
    topLink.click(function(e) {
        e.preventDefault();
        $('html, body').animate({scrollTop: 0}, 800, function() {
            topLink.fadeOut(); // Optionally hide the link immediately after click
        });
    });

    $(document).on('keyup', '#nip', function(e) {
        $('.duplicate').remove();
        let nip = $(this).val();
        if (nip.length > 9) {
            fetchNIP(nip, 'gus');
        }
    });

    $(document).on('click', '.showPhotos', function(e) {
        e.preventDefault();

        // Get db row ID
        let rowid = $(this).parent().parent().find('td[data-column="id"]').text();
        $('#deletePhoto').attr('data-id', rowid);

        let href = $(this).attr('href').split('/');
        let path = href[0];
        let file = href[1].split(',');
        let popp = '';
        file.forEach((value, index) => {
            if (index === 0) {
                popp += `<img src="uploads/${path}/${value}">`;
            } else {
                popp += `<img style="display:none" src="uploads/${path}/${value}">`;
            }
        });
        $('#showPhotos').html(popp);

        var screenWidth = window.innerWidth * 0.95; //$(window).width() * 0.9
        var screenHeight = window.innerHeight * 0.95; //$(window).height() * 0.9
        //console.log(screenWidth + ' : ' + screenHeight);
        if (screenWidth < screenHeight) {
            $('#showPhotos img').css('width', screenWidth + 'px');
        } else {
            $('#showPhotos img').css('height', screenHeight + 'px');
        }

        $('#overlay, #showPhotos').fadeIn();
        if (file.length > 1) {
            $('#prevPhoto, #nextPhoto').show();
            $('#closePhoto').css('right', '90px').show();
            $('#deletePhoto').css('left', '10px').show();
            $('#movePhotos').fadeIn();
        } else {
            $('#prevPhoto, #nextPhoto').hide();
            $('#closePhoto').css('right', '10px').show();
            $('#deletePhoto').css('left', '10px').show();
            $('#movePhotos').fadeIn();
        }
        //let getid = $(this).parent().parent().attr('id').replace('id_', '');
        //showPhotos(getid);

        if ($(this).hasClass('cookie')) {
            $('#deletePhoto').hide();
            hideDel = true;
        } else {
            $('#deletePhoto').show();
            hideDel = false;
        }
    });

    $('#nextPhoto').click(function() {
        let currentImg = $('#showPhotos img:visible');
        let nextImg = currentImg.next('img');
        if (nextImg.length) {
            currentImg.hide();
            nextImg.show();
            // Don't display delete button if photo is deleted
            if (nextImg.attr('src') == 'images/deleted.png' || hideDel) {
                $('#deletePhoto').hide();
            } else {
                $('#deletePhoto').show().css('left', '90px');
            }
            $('#prevPhoto').css('opacity', '1'); // Ensure prevPhoto is always clickable if not on the first image
            if (!nextImg.next('img').length) { // If nextImg is the last image
                $('#closePhoto').css('right', '10px');
                $('#nextPhoto').css('opacity', '0');
            }
        }
    });
  
    $('#prevPhoto').click(function() {
        $('#closePhoto').css('right', '90px');
        let currentImg = $('#showPhotos img:visible');
        let prevImg = currentImg.prev('img');
        if (prevImg.length) {
            currentImg.hide();
            prevImg.show();
            // Don't display delete button if photo is deleted
            if (prevImg.attr('src') == 'images/deleted.png' || hideDel) {
                $('#deletePhoto').hide();
            } else {
                $('#deletePhoto').show().css('left', '90px');
            }
            $('#nextPhoto').css('opacity', '1'); // Ensure nextPhoto is always clickable if not on the last image
            if (!prevImg.prev('img').length) { // If prevImg is the first image
                $('#prevPhoto').css('opacity', '0');
                $('#deletePhoto').css('left', '10px');
            }
        }
    });
    
    // Handle changes in input and textarea fields (including type="date")
    $(document).on('input change', '#recordForm input:not(#history):not(#user)', function() { // #recordForm textarea:not(#super)
        // If there's at least one character, remove 'missed'
        if ($(this).val() !== '') {
            $(this).removeClass('missed');
        }
    });

    // Handle changes in select elements
    $(document).on('change', '#recordForm select', function() {
        // If the first option is not selected, remove 'missed'
        if (this.selectedIndex !== 0) {
            $(this).removeClass('missed');
        }
    });

    $(document).on('click', '#closePhoto', function(e) {
        $('#overlay, #showPhotos, #movePhotos').fadeOut(200, function(){
            $('#showPhotos img').hide();
            $('#showPhotos img:first').show();
            $('#prevPhoto').css('opacity', '0');
            $('#nextPhoto').css('opacity', '1');
        });
    });

    $(document).on('click', '#deletePhoto', function(e) {
        if (confirm("Potwierdź usunięcie zdjęcia")) {
            const id = $(this).data('id');
            const src = $('#showPhotos img:visible').attr('src'); // Get the visible photo to be deleted
            const dbr = $('tbody tr[id="id_' + id + '"]').find('td[data-column="attachments"] a').attr('href'); // dbr = database record
            let array = [src, dbr];
            deleteRecord(id, dbTable, array, company);
        }
    });
    
    $(document).on('click', '.remove_button', function(e) {
        blockCounter = blockCounter - 1;
        let count = $('.duplicate').length;
        if (count < 10) {
            $('.add_button').val('Dodaj');
        }
        $(this).parent().remove(); // remove the whole .duplicate block

        // Update sms
        let smsNote = $("#send_sms_note").val();
        let partsCount = $('input.part_name').length;
        if (partsCount > 1) {
            smsNote = smsNote.replace('Twoje (10) części zostały przyjęte', 'Twoje (9) części zostały przyjęte');
            smsNote = smsNote.replace(/\([1-9]\)/, '(' + partsCount + ')');
            if (partsCount < 5) {
                smsNote = smsNote.replace('części zostało przyjęte', 'części zostały przyjęte');
            }
            $("#send_sms_note").val(smsNote);
        } else {
            smsNote = smsNote.replace('Twoje (2) części zostały przyjęte', 'Twoja (1) część została przyjęta');
            $("#send_sms_note").val(smsNote);
        }
        
        // Sms length
        let charCount = [...$('#send_sms_note').val()].reduce((acc, char) => acc + (/[ąćęłńóśźż]/.test(char) ? 2 : 1), 0);
        $('#sms_counter span').eq(1).text(160 - charCount);
    });
    
    $(document).on('click', '.add_button', function(e) {
        let count = $('.duplicate').length;
        if ($('#duplicate').find('.part_name').is(':disabled')) {
            $('#duplicate').find('input, select, textarea').prop('disabled', false);
            $('#duplicate').css('opacity', '1');
        } else if (count >= 9) {
            $('.add_button').val('Max 10');
        } else {
            blockCounter = blockCounter + 1;
            let $clone = $('#duplicate').clone();
            $('.part_name').css({'max-width': '337px', 'margin-right': '5px'});

            $clone.find('.part_name').val(''); // input
            $clone.find('input[name="attachments_edit[]"]').val('');
            $clone.find('.verification_quote').val('');
            $clone.find('.repair_quote').val('');
            $clone.find('.interference_note_box').find('textarea').val(''); // textarea
            $clone.find('.interference_checkbox').prop('checked', false); // uncheck the checkbox
            $clone.find('.interference_note_box').css('display', 'none'); // hide the textarea
            $clone.find('.attachments_button').text('Dodaj (Max 2)');
            $clone.find('input[name="part_name_index[]"]').val(blockCounter);
            $clone.find('label[for="attachments"]').attr('for', 'attachments_' + blockCounter);
            $clone.find('input[name="attachments_path"]').attr('name', 'attachments_path_' + blockCounter);
            $clone.find('input[name="attachments[]"]').attr('name', 'attachments_' + blockCounter + '[]');
            //$clone.find('.editTip2').remove();
            
            $('#duplicate').before($clone);
            
            $(this).removeClass('add_button').addClass('remove_button').val('Usuń');
            $(this).parent().removeAttr('id').addClass('duplicate');
            $(this).parent().css({'margin-bottom': '0', 'margin-top': '20px'});
            
            $('.duplicate').find('.editTip2').remove(); // Don't clone tips
            
            // Update sms
            let smsNote = $("#send_sms_note").val();
            let partsCount = $('input.part_name').length;
            smsNote = smsNote.replace('Twoja (1) część została przyjęta', 'Twoje (' + partsCount + ') części zostały przyjęte');
            smsNote = smsNote.replace(/\([1-9]|10\)/, '(' + partsCount);
            if (partsCount > 4) {
                smsNote = smsNote.replace('części zostały przyjęte', 'części zostało przyjęte');
            }
            $("#send_sms_note").val(smsNote);
            
            // Sms length
            let charCount = [...$('#send_sms_note').val()].reduce((acc, char) => acc + (/[ąćęłńóśźż]/.test(char) ? 2 : 1), 0);
            $('#sms_counter span').eq(1).text(160 - charCount);

            $('.duplicate').find('#editTip').hide();
        }
    });

    $(document).on('click', '#changeCompany', function(e) {
        if (dbTable !== 'panel' && dbTable !== 'calendar') {
            company = company == 'Hydro-Dyna' ? 'Hydro-Dex' : 'Hydro-Dyna';
            setCookie('company', company, 365);
            $('.logo').attr('src', 'images/' + company + '.png'); // Mark active tab
            $('#company').val(company);
            $('#company').trigger('change');
        }
    });

    $(document).on('click', '#changeCompany2', function(e) {
        if (dbTable !== 'panel' && dbTable !== 'calendar') {
            let id = $('#id').val();
            if (id !== null && id !== '') {
                // Update - only for the current row
                company_edit = $('#recordForm #company').val();
                //if (company_edit == 'Hydro-Dex') { company_edit = 'Hydro-Dyna'; } else { company_edit = 'Hydro-Dex'; }
                company_edit = (company_edit == 'Hydro-Dex') ? 'Hydro-Dyna' : 'Hydro-Dex';
                $('#recordForm #company').val(company_edit);
                $('#recordForm .logo').attr('src', 'images/' + company_edit + '.png');
            } else {
                company = company == 'Hydro-Dyna' ? 'Hydro-Dex' : 'Hydro-Dyna';
                setCookie('company', company, 365);
                $('.logo').attr('src', 'images/' + company + '.png'); // Mark active tab
                $('#company').val(company);
                $('#company').trigger('change');
            }
        }
    });

    $(document).on('click', '.attachments_button', function() {
        $(this).parent().find('.attachments').click();
    });
    $(document).on('change', '.attachments', function() {
        var numberOfFiles = $(this)[0].files.length;
        if (numberOfFiles > 2) {
            alert('Możesz dodać maksymalnie 2 zdjęcia');
            $(this).val(''); // Clear the selected files
            $(this).parent().find('.attachments_button').text('Dodaj zdjęcia'); // Reset the button text
        } else {
            var label = numberOfFiles > 1 ? 'dodano ' + numberOfFiles + ' zdjęcia' : 'dodano 1 zdjęcie';
            $(this).parent().find('.attachments_button').text(label);
        }
    });

    // Add photo
    $('tbody').on('click', '.photoBtn', function() {
        const id = $(this).data('id');
        fetchRecordDetails(id, dbTable, true); // Fetch details and populate the form

        //$('.attachments').click();
        $('.attachments').attr('capture', 'camera').click();
        setTimeout(function() {
            $('.attachments').removeAttr('capture');
        }, 100);
    });

    // Listen for changes on the select element
    $(document).on('change', '#status', function() {

        if ($(this).val() === "Zlecona weryfikacja") {
            $('#blockVerification').slideDown(250);
        } else if ($('#verification_date_count').val() == '' && $('#verification_date').val() == '') {
            $('#blockVerification').slideUp(250);
        }

        if ($(this).val() === "Zlecona naprawa") {
            $('#blockRepair').slideDown(250);
        } else if ($('#repair_date_count').val() == '' && $('#repair_date').val() == '') {
            $('#blockRepair').slideUp(250);
        }
        
        if ($(this).val() === "Wydać bez naprawy") {
            $('#assemble_box').slideDown();
            $('#archived').prop('checked', false);
        } else if ($(this).val() === 'Zakończono') {
            $('#archived').prop('checked', true);
        } else {
            $('#assemble_box').slideUp();
            $('#archived').prop('checked', false);
        }
        
        if ($(this).val() === "Wyceniona naprawa" || 
            $(this).val() === "Zlecona naprawa" || 
            $(this).val() === "Zrobić test" || 
            $(this).val() === "Wydać po naprawie" || 
            $(this).val() === "Reklamacja") {
            $('#offersent_box').slideDown();
            $('#archived').prop('checked', false);
        } else {
            $('#offersent_box').slideUp();
        }
        
    });

    $('#recordForm').on('click', function(event) {
        if ($(event.target).hasClass('cancelBtn') || $(event.target).is('#saveBtn')) {
            return; // Do nothing if .cancelBtn or #saveBtn is clicked
        }
        setTimeout(function(){
            popupPosition();
        }, 500);
    });

    $(document).on('keydown', function(e) {
        if (e.key === "Escape" || e.keyCode === 27) { // Checks for the Escape key
            $('.cancelBtn').trigger('click'); // Triggers click event on the button with id 'myButton'
            $('#closePhoto').trigger('click');
            $('#notification, .notification_bg').hide();
            if (typeof timer !== 'undefined') {
                clearTimeout(timer);
            }
        } else if ((e.key === "ArrowRight" || e.keyCode === 39) && $('#nextPhoto').is(':visible')) {
            e.preventDefault();
            $('#nextPhoto').trigger('click');
        } else if ((e.key === "ArrowLeft" || e.keyCode === 37) && $('#prevPhoto').is(':visible')) {
            e.preventDefault();
            $('#prevPhoto').trigger('click');
        }
    });

    $(document).on('change', '.interference_checkbox', function() {
        let container = $(this).closest('#recordForm');
        let noteBox = container.find('.interference_note_box').eq($(this).index('.interference_checkbox'));
        if ($(this).is(':checked')) {
            noteBox.slideDown();
        } else {
            noteBox.slideUp();
        }
    });

    $(document).on('change', '#send_sms', function() {
        if ($(this).is(':checked')) {
            $('#send_sms_note_box').slideDown();
            $('#sms_counter').fadeIn();
        } else {
            $('#send_sms_note_box').slideUp();
            $('#sms_counter').fadeOut();
        }
    });

    $(document).on('change', '#client_address', function() {
        if ($(this).is(':checked')) {
            $('#client_address_box').slideDown();
        } else {
            $('#client_address_box').slideUp();
        }
    });
    
    $(document).on('change', '#individual', function() {
        if ($(this).is(':checked')) {
            nip_val = $('#nip').val();
            $('#nip').prop('disabled', true);
            $('#nip').attr('placeholder', '').val('').css('font-weight', 'normal');
            $('#nip, label[for="nip"]').css('opacity', '.5');
        } else {
            $('#nip').prop('disabled', false);
            $('#nip').attr('placeholder', 'NIP').val(nip_val).css('font-weight', 'bold');
            $('#nip, label[for="nip"]').css('opacity', '1');
        }
    });

    $(document).on('input', '#send_sms_note', function() {
        const MAX_SMS_LENGTH = 160;
        let inputText = $(this).val();

        // Calculate the character count considering special characters
        let charCount = 0;
        for (let i = 0; i < inputText.length; i++) {
            if (inputText[i].match(/[ąćęłńóśźż]/)) {
                charCount += 2;
            } else {
                charCount += 1;
            }
        }

        // Calculate remaining characters
        let remainingChars = MAX_SMS_LENGTH - (charCount % MAX_SMS_LENGTH || MAX_SMS_LENGTH);
        
        // Calculate the number of SMS required
        let smsCount = Math.ceil(charCount / MAX_SMS_LENGTH);

        // Update the SMS counter spans
        $('#sms_counter span').eq(0).text(smsCount);
        $('#sms_counter span').eq(1).text(remainingChars);
    });

    $(document).on('input', '.verification_quote', function() {
        let allEmpty = true;
        $('.verification_quote').each(function() {
            if ($(this).val() != '') {
                allEmpty = false;
                return false; // exit the loop early if a non-empty value is found
            }
        });
        if (allEmpty) {
            $('#blockVerification').slideUp(250);
        } else {
            $('#blockVerification').slideDown(250);
            //$('#status').val('Zlecona weryfikacja');
        }
    });

    $(document).on('input', '.repair_quote', function() {
        let allEmpty = true;
        $('.repair_quote').each(function() {
            if ($(this).val() != '') {
                allEmpty = false;
                return false; // exit the loop early if a non-empty value is found
            }
        });
        if (allEmpty) {
            $('#blockRepair').slideUp(250);
        } else {
            $('#blockRepair').slideDown(250);
            $('#offersent_box').slideDown(250);
            if (!$('#repair_accepted').prop('checked')) {
                $('#status').val('Wyceniona naprawa');
            }
        }
    });

    $(document).on('click', '.notification_bg_white', function() {
        if ($('#recordForm #id').val() !== '') {
            $('.notification_bg_white').hide();
            $('.suggestions').hide();
            suggestionItemClicked = true;
        }
    });

    //$(document).on('keyup', '#recordForm #name, #recordForm #lastname', function() {
    $(document).on('keyup', '#recordForm #name, #recordForm #clientid', function() {
        let inputField = $(this);
        let inputId = inputField.attr("id");
        let inputValue = inputField.val();
        let suggestionCount = $("#suggestionCount").val(); // Get the selected suggestion count
        let suggestionBoxId = "nameSuggestions"; //inputId + "Suggestions";
        let suggestionBox = $("#" + suggestionBoxId);
        //console.log(suggestionBox);
        //console.log(inputId, inputValue, suggestionCount, suggestionBox);
    
        hideAllSuggestions(inputId);
        if (inputValue.length > 1) {
            fetchSuggestions(inputId, inputValue, suggestionCount, suggestionBox);
            // block
            $('.notification_bg_white').show();
            $('#name, label[for="name"], #clientid, label[for="clientid"]').css({'position': 'relative', 'z-index': '0'}); // reset
            $('#' + inputId + ', label[for="' + inputId + '"]').css({'position': 'relative', 'z-index': '99'});
        } else {
            suggestionBox.empty().hide();
            // unblock
            $('.notification_bg_white').hide();
        }
    });

    var suggestionItemClicked = false;
    $(document).on('mousedown', '.suggestion-item', function() {
        suggestionItemClicked = true;
    });
    /*$(document).on('blur', '#recordForm #name', function() {
        if (!suggestionItemClicked) {
            $('.suggestions').hide();
        }
        suggestionItemClicked = false;
    });*/
    $(document).on('mousedown', '.suggestions', function() {
        suggestionItemClicked = true;
    });
    
    $(document).on('click', '.suggestion-item', function() {

        // unblock
        $('.notification_bg_white').hide();

        let data = $(this).data();
        
        if (data.clientid == '') {
            data.clientid = clientid_default;
            data.orderid = data.clientid + data.orderid;
            data.receiptid = data.clientid + data.receiptid;
        }

        // Reset - used also in selectNIP function
        //$("#clientid, #firstname, #lastname, #town, #postcode, #street, #house_no, #flat_no, #type, #phone, #name_custom, #name, #country, #client_orders").val('');
        let regex = /\d{5}\/\d{3}/;
        if ($('#send_sms_note').length > 0) {
            let sms_note = $("#send_sms_note").val().replace(regex, data.orderid)
                                                    .replace('zlecenia o numerach', 'zlecenie o numerze')
                                                    .replace('zostały przyjęte', 'zostało przyjęte');
            $("#send_sms_note").val(sms_note);
        }

        $('#clientid').val(data.clientid);
        $('#orderid').val(data.orderid);
        $('#client_orders').val(data.client_orders);
        $('#receiptid').val(data.receiptid);
        $('#firstname').val(data.firstname);
        $('#lastname').val(data.lastname);
        $('#phone').val(data.phone);
        $('#email').val(data.email);
        $('#name').val(data.name);
        $('#nip').val(data.nip);
        // id, street, house_no, flat_no, postcode, town

        $('#name_custom').val(data.name_custom);
        $('#phone_additional').val(data.phone_additional);
        $('#street').val(data.street);
        $('#house_no').val(data.house_no);
        $('#flat_no').val(data.flat_no);
        $('#postcode').val(data.postcode);
        $('#town').val(data.town);
        $('#country').val(data.country);
        $('#import_db').val(data.import_db);
        $('#client_exists_mark').val('1'); // client exists if was chosen from the dropdown suggestions

        $('.suggestions').hide();

        // NIP
        if (data.nip == null || data.nip == '') {
            $('#individual').prop('checked', true);
            $('#nip').prop('disabled', true);
            $('#nip').attr('placeholder', '').val('').css('font-weight', 'normal');
            $('#nip, label[for="nip"]').css('opacity', '.5');
        }
        if (data.nip.toString().length > 9) {
            fetchNIP(data.nip, 'local'); // Populate from GUS
        }
    });
    
    // Write anything in any of the 'verification' fields and check 'confirmation' box automatically
    $(document).on('keyup', '#verification_date_start, #verification_date_count, #verification_date, #verification_date_quote', function() {
        $('#verification_accepted').prop('checked', true);
    });
    $(document).on('change', '#verification_date_start, #verification_date', function() {
        if ($(this).val() !== "") {
            $('#verification_accepted').prop('checked', true);
        }
    });

    $(document).on('keyup', '#repair_date_start, #repair_date_count, #repair_date, #repair_date_quote', function() {
        //$('#repair_accepted').prop('checked', true);
        $('#repair_accepted').prop('checked', true).trigger('change'); // trigger mimics manual select
    });
    $(document).on('change', '#repair_date_start, #repair_date', function() {
        if ($(this).val() !== "") {
            $('#repair_accepted').prop('checked', true);
        }
    });

    $(document).on('change', '#repair_accepted', function() {
        $('#status').val('Zlecona naprawa');
    });

    // MOBILE
    if (window.innerWidth >= 1080) {
        var header = $('.header'); // Reference to the header element
        var documentWidth = $(document).width(); // Get the width of the document
        $(window).scroll(function() {
            var scrollX = $(window).scrollLeft(); // Get current horizontal scroll
            // Ensure the header doesn't scroll beyond the document width
            if (scrollX - $(window).width() < documentWidth) {
                header.css('left', scrollX + 'px'); // Adjust header position based on scroll
            }
        });
    }

    $(document).on('mouseover', '.showPrint', function() { 
        $(this).next('.printBox').show();
    });

    $(document).on('mouseleave', '.printBox', function() { 
        $(this).hide();
    });

    $(window).resize(adjustTableMargin); // Call when window is resized

    /*$("#recordsTable thead tr#columnNames").sortable({
        items: "th:not(:last-child)", // Exclude the last column (Options) from being sortable
        placeholder: "ui-sortable-placeholder",
        stop: function (event, ui) {
            var columnIndex = ui.item.index();
            var previousIndex = ui.item.data('index');

            if (columnIndex !== previousIndex) {
                reorderColumns(previousIndex, columnIndex);
                updateColumnDataIndexes();
            }
        }
    }).disableSelection();

    // Function to reorder table columns
    function reorderColumns(fromIndex, toIndex) {
        $('#recordsTable').find('tr').each(function () {
            var row = $(this);
            var cells = row.children('th, td');

            if (toIndex < fromIndex) {
                cells.eq(toIndex).before(cells.eq(fromIndex));
            } else {
                cells.eq(toIndex).after(cells.eq(fromIndex));
            }
        });
    }

    // Update data-index attribute to keep track of original positions
    function updateColumnDataIndexes() {
        $("#recordsTable thead tr#columnNames th").each(function (index) {
            $(this).data('index', index);
        });
    }

    // Initial update of data-index
    updateColumnDataIndexes();*/

    // jQuery code to remove border on mouse over and add back on mouse out
    $(document).on('mouseenter', '.dayOut', function() {
        $(this).css('border', '1px solid #777');
    });

    $(document).on('mouseleave', '.dayOut', function() {
        $(this).css('border', '1px solid #FFF');
        const parentRow = $(this).closest('tr');
        if (parentRow.hasClass('cal-week-1') || parentRow.hasClass('cal-week-2')) {
            $(this).css('border-bottom', '3px solid #000');
            if ($(this).is(parentRow.find('.dayOut').last())) {
                $(this).css('border-right', '3px solid #000');
            }
        } else if (parentRow.hasClass('cal-week-4') || parentRow.hasClass('cal-week-5') || parentRow.hasClass('cal-week-6')) {
            $(this).css('border-top', '3px solid #000');
            if ($(this).is(parentRow.find('.dayOut').first())) {
                $(this).css('border-left', '3px solid #000');
            }
        }
    });

    $(document).on('click', '#calendarPrev', function() {
        month = month - 1;
        if (month == 0) { month = 12; year = year - 1; }
        fetchCalendar(month, year);
    });
    $(document).on('click', '#calendarNext', function() {
        month = month + 1;
        if (month == 13) { month = 1; year = year + 1; }
        fetchCalendar(month, year);
    });
    $(document).on('click', '#calendarReset', function() {
        month = currentDateX.getMonth() + 1;
        year = currentDateX.getFullYear();
        fetchCalendar(month, year);
    });

    $(document).on('click', '#calendarAdd', function() {
        addEvent('');
    });
    $(document).on('mouseenter', '.event-summary-row', function() {
        if ($(this).has('.specialEvent')) {
            $(this).parent().find('.eventDelete').show();
            $(this).parent().find('.eventEdit').show();
        }
    });
    $(document).on('mouseleave', '.event-summary-row', function() {
        if ($(this).has('.specialEvent')) {
            $(this).parent().find('.eventDelete').hide();
            $(this).parent().find('.eventEdit').hide();
        }
    });
    $(document).on('click', '.eventDelete', function() {
        if (confirm("Potwierdź usunięcie")) {
            const id = $(this).data('id');
            deleteRecord(id, 'events', 'delete', ''); // Fetch details and populate the form
        }
    });
    $(document).on('click', '.eventEdit', function() {
        const id = $(this).data('id');
        addEvent(id);
    });

    $(document).on('click', '#learn', function() {
        //$('.editTip2').toggle();
        if ($('.editTip2').is(':visible')) {
            $('#nip, label[for="nip"]').css('margin-top','40px');
            $('.editTip2').slideUp();
        } else {
            $('#nip, label[for="nip"]').css('margin-top','0');
            $('.editTip2').slideDown();
        }
    });

    // Event listener for scroll to check if the user has reached near the bottom of the page
    /*$(window).on('scroll', function() {
        console.log(($(window).scrollTop() + $(window).height()) + ' >= ' + ($(document).height() - 50));
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 10) {
            $('tr.hideMe').slice(0, 100).removeClass('hideMe');
        }
    });*/

    $(window).on('scroll', function() {
        var $loadMe = $('.loadMe');
        if (isElementInViewport($loadMe)) {
            var $hiddenRows = $('tr.hideMe');
            if ($hiddenRows.length > 0) {
                var $nextRows = $hiddenRows.slice(0, 100);
                $nextRows.removeClass('hideMe');
                $loadMe.removeClass('loadMe');
                $nextRows.last().addClass('loadMe');
            }
        }
    });
});




















function isElementInViewport(el) {
    var rect = el[0].getBoundingClientRect();
    return (
        rect.top < (window.innerHeight || document.documentElement.clientHeight) &&
        rect.bottom > 0
    );
}

function addEvent(id) {

    formContent = '<br>';

    formContent += '<label for="cal_date_from">Data rozpoczęcia</label>';
    formContent += '<input placeholder="Data rozpoczęcia" type="date" id="cal_date_from" name="date_from" value=""><br>';
    formContent += '<label for="cal_date_to">Data zakończenia</label>';
    formContent += '<input placeholder="Data zakończenia" type="date" id="cal_date_to" name="date_to" value=""><br>';

    formContent += '<label for="cal_event">Tytuł wydarzenia</label>';
    formContent += '<input placeholder="Tytuł wydarzenia" type="text" id="cal_event" name="event" value=""><br>';

    formContent += '<div class="editTip2 block"><i style="margin-bottom:20px" class="fa-solid fa-circle-info"></i><p>Notatka jest opcjonalna - pojawia się w czarnym dymku po najechaniu myszką na wydarzenie</p></div>';

    formContent += '<label for="cal_note">Notatka</label>';
    formContent += '<input placeholder="Notatka" type="text" id="cal_note" name="note" value=""><br>';

    formContent += '<input placeholder="user" type="hidden" name="user" value="">';

    formContent += '<button style="margin-top:15px" type="button" id="saveBtn_event">Zapisz</button>';
    formContent += '<button style="margin-top:15px" type="button" class="cancelBtn">Anuluj</button>';
    
    $('#recordForm').html(formContent);

    // Check the screen height and the height of the '.popup' element
    var screenHeight = window.innerHeight; // $(window).height(); - jQuery confused window with document for some reason
    var popupHeight = $('#formPopup').outerHeight(); // document.getElementById('formPopup').offsetHeight;
    
    if (popupHeight > screenHeight) {
        $('#formPopup').css({
            'position': 'absolute',
            'top': '40px',
            'transform': 'translate(-50%, 0)'
        });
    } else { // Get back to default from style.css
        $('#formPopup').css({
            'position': 'fixed',
            'top': '50%',
            'transform': 'translate(-50%, -50%)'
        });
    }

    if (id != '') {
        fetchRecordDetails(id, 'events');
    }

    $('#overlay, #formPopup').fadeIn();

    $('#saveBtn_event').on('click', function() {
        let letsProceed = true;
        let cal_date_from = $('#cal_date_from').val();
        let cal_date_to = $('#cal_date_to').val();
        let cal_event = $('#cal_event').val();
        let cal_note = $('#cal_note').val();
        if ($('#cal_date_from').val() === '') {
            $('#cal_date_from').addClass('missed');
            letsProceed = false;
        }
        if ($('#cal_date_to').val() === '') {
            $('#cal_date_to').addClass('missed');
            letsProceed = false;
        }
        if ($('#cal_event').val() === '') {
            $('#cal_event').addClass('missed');
            letsProceed = false;
        }
        /*if ($('#cal_note').val() === '') {
            $('#cal_note').addClass('missed');
            letsProceed = false;
        }*/
        if (letsProceed) {
            saveEvent(id, cal_date_from, cal_date_to, cal_event, cal_note);
        }
    });
}

function saveEvent(id, cal_date_from, cal_date_to, cal_event, cal_note) {
    $.ajax({
        url: 'admin/saveEvent.php', // URL to your PHP script for fetching records
        type: 'POST',
        dataType: 'json',
        data: {id: id, date_from: cal_date_from, date_to: cal_date_to, event: cal_event, note: cal_note},
        success: function(response) {
            //console.log(response);
            fetchCalendar(month, year);
            $('#overlay, #formPopup').fadeOut();
        },
        error: function(xhr, status, error) {
            console.error("Error fetching records: ", error);
        }
    });
}

function fetchCalendar(month, year) {
    //console.log(':', month, year);
    $('#loading').show();
    $.ajax({
        url: 'admin/fetchCalendar.php', // URL to your PHP script for fetching records
        type: 'POST',
        dataType: 'json',
        data: {month: month, year: year},
        success: function(response) {
            //console.log(response);
            //console.log(month, year);

            $('#calendarContainer').html(response.calendar);
            $('.day').attr('title', '');

            // Add borders to the first row
            $('.cal-week-1 .dayOut, .cal-week-2 .dayOut').css('border-bottom', '3px solid #333');
            $('.cal-week-1 .dayOut:last, .cal-week-2 .dayOut:last').css('border-right', '3px solid #333');
    
            // Add borders to the last row
            $('.cal-week-5 .dayOut, .cal-week-6 .dayOut').css('border-top', '3px solid #333');
            $('.cal-week-5 .dayOut:first, .cal-week-6 .dayOut:first').css('border-left', '3px solid #333');

            let date = $('.calendar-title th').text();
            $('#calendarMenu span').text(date);

            $('#rowCount').text(response.count + ' wyników');
            $('#loading').hide();

            $('.today').addClass('shake');
            setTimeout(function() {
                $('.today').removeClass('shake');
            }, 2500);
        },
        error: function(xhr, status, error) {
            console.error("Error fetching records: ", error);
        }
    });
}

function fetchSuggestions(inputId, inputValue, suggestionCount, suggestionBox) {
    $.ajax({
    url: "admin/fetchSuggestions.php",
    method: "POST",
    dataType: "json",
    data: {
        field: inputId,
        search: inputValue,
        count: suggestionCount // Pass the selected count to the server
    },
    success: function(response) {
        suggestionBox.empty().show();

        if (inputId == 'clientid') {
            $('form#recordForm .suggestions').css('margin-top', '300px');
        } else {
            $('form#recordForm .suggestions').css('margin-top', '0');
        }

        if (response.success ) {
            if (response.data == '') {
                $('.notification_bg_white').hide();
                suggestionBox.empty().hide();
            } else {
               response.data.forEach(function(item) {
                    if (item.flat_no === null) {
                        item.flat_no = '';
                    } else {
                        item.flat_no = '/' + item.flat_no;
                    }
                    // Change null to nothing
                    item.id = noNull(item.id);
                    item.clientid = noNull(item.clientid);
                    item.orderid = noNull(item.orderid);
                    item.client_orders = noNull(item.client_orders);
                    item.receiptid = noNull(item.receiptid);
                    item.firstname = noNull(item.firstname);
                    item.lastname = noNull(item.lastname);
                    item.phone = noNull(item.phone);
                    item.phone_additional = noNull(item.phone_additional);
                    item.email = noNull(item.email);
                    item.name = noNull(item.name);
                    item.name_custom = noNull(item.name_custom);
                    item.nip = noNull(item.nip);
                    item.street = noNull(item.street);
                    item.house_no = noNull(item.house_no);
                    item.flat_no = noNull(item.flat_no);
                    item.postcode = noNull(item.postcode);
                    item.town = noNull(item.town);
                    item.country = noNull(item.country);
                    item.import_db = noNull(item.import_db);
    
                    let suggestionHtml = `
                    <div class="suggestion-item" data-id="${item.id}" data-clientid="${item.clientid}" data-orderid="${item.orderid}" data-receiptid="${item.receiptid}" data-client_orders="${item.client_orders}" data-firstname="${item.firstname}" data-lastname="${item.lastname}" data-phone="${item.phone}" data-phone_additional="${item.phone_additional}" data-email="${item.email}" data-name="${item.name}" data-name_custom="${item.name_custom}" data-nip="${item.nip}" data-street="${item.street}" data-house_no="${item.house_no}" data-flat_no="${item.flat_no}" data-postcode="${item.postcode}" data-town="${item.town}" data-country="${item.country}" data-import_db="${item.import_db}">
                        <span style="color:black">${item.name}</span><br>
                        <span>NIP:</span> ${item.nip}&nbsp;&nbsp;
                        <span>IMIĘ NAZWISKO:</span> ${item.firstname} ${item.lastname}<br>
                        <span>ADRES:</span> ${item.street} ${item.house_no}${item.flat_no}, ${item.postcode} ${item.town}
                    </div>`;
                    //<span ${inputId === 'name' ? 'style="color:black;"' : ''}>${item.name}</span>&nbsp;&bull;
                    suggestionBox.append(suggestionHtml);
                });
            }
        } else {
            suggestionBox.hide();
        }
    }
    });      
}
            
function resetSearch() {
    //e.preventDefault();
    $('.searchInput').val('');
    Object.keys(columnSearches).forEach(function(index) {
        columnSearches[index] = '';
    });
    //$('#recordsTable tbody tr').show();
    if (archived == '1') {
        $('tbody tr').hide();
        $('tbody tr.archived').show();
    } else {
        $('tbody tr').show();
        $('tbody tr.archived').hide();
    }
    updateRowCount(dbTable);
}

function adjustTableMargin() {
    var screenWidth = $(window).width();
    var tableWidth = $('#recordsTable').outerWidth();

    if (tableWidth > screenWidth) {
        $('#recordsTable').css('margin-left', '-40px');
    } else {
        $('#recordsTable').css('margin-left', '0px');
    }
}

function fetchNIP(nip, type) {
    $("#loader").show().text("Checking...");
    $.ajax({type: "POST",
        url: "functions/nip.php", // Path to the script that fetches from GUS
        data: {nip: nip},
        success: function(response) {
            //console.log(response);
            $("#loader").hide();
            let data = JSON.parse(response);

            // Client exists / notexists
            // If more companies found for one NIP
            // GUS
            if (type == 'gus' && data.length > 1) {
                $('#name').replaceWith('<select id="name" name="name"><option value="">Wybierz firmę</option></select>');
                data.forEach(function(element, index) {
                    $('#name').append($('<option>', { 'data-id': index, value: element.name, text: element.name }));
                });
                $('#name').addClass('highlight_red').val('');
                // After choose from dropdown
                $('#name').on('change', function() {
                    $(this).removeClass('highlight_red');
                    var index = $(this).find('option:selected').attr('data-id');
                    selectNIP(index, data[index], type); // Populate
                    gusData = data[index]; // For save client function
                });
            // LOCAL
            } else {
                $('#name').replaceWith('<input placeholder="Nazwa" type="text" id="name" name="name">');
                $('#name').removeClass('highlight_red').val('');
                selectNIP(0, data[0], type); // Populate
                gusData = data[0]; // For save client function
            }

            // GUS
            if (type == 'gus') {
                $("#client_exists").html(data[0].clientExists);
                $("#client_exists_mark").val(data[0].clientExistsMark);
                if (data[0].success === false) {
                    $("#nip").removeClass('highlight_green').addClass('highlight_red');
                } else {
                    $("#nip").removeClass('highlight_red').addClass('highlight_green');
                }
                
                // Show 
                if (data[0].branch.length > 0) {
                    //console.log(data[0].branch);

                    let inputField = $("#recordForm #name");
                    let inputId = inputField.attr("id");
                    let inputValue = data[0].branch;
                    let suggestionCount = $("#suggestionCount").val(); // Get the selected suggestion count
                    let suggestionBoxId = inputId + "Suggestions";
                    let suggestionBox = $("#" + suggestionBoxId);
                
                    hideAllSuggestions(inputId);
                    if (inputValue.length > 1) {
                        fetchSuggestions(inputId, inputValue, suggestionCount, suggestionBox);
                        $("#name").addClass('highlight_red').val('Wybierz oddział');
                    } else {
                        suggestionBox.empty().hide();
                    }
                }
            }
        },
        error: function() {
            $("#nip").removeClass('highlight_green').addClass('highlight_red');
            $("#loader").hide();
            alert("Nie pobrano danych z bazy GUS");
        }
    });  
}

function selectNIP(index, data, type) {

    // Reset
    if (type == 'gus') {
        $("#firstname, #lastname, #town, #postcode, #street, #house_no, #name_custom, #flat_no, #type, #phone, #phone_additional, #email, #country").val('');
        $('#clientid').val(clientid_default);
        $('#client_orders').val(clientorders_default);
        $('#name option[data-id=' + index + ']').prop('selected', true);
    }

    if (data.success !== false) {

        if ($('#send_sms_note').length > 0) {
            // Change receiptid in sms
            let regex = /\d{5}\/\d{3}/;
            if (typeof data.orderid === 'undefined') {
                data.orderid = orderid_default;
            }
            let sms_note = $("#send_sms_note").val().replace(regex, data.orderid);
            $("#send_sms_note").val(sms_note);
    
            // Update the sms chars count
            let charCount = [...$('#send_sms_note').val()].reduce((acc, char) => acc + (/[ąćęłńóśźż]/.test(char) ? 2 : 1), 0);
            $('#sms_counter span').eq(1).text(160 - charCount);
        }

        // Populate
        if ($("#firstname").val() == '') {
            $("#firstname").val(data.firstname).prop('readonly', false);
        }
        if ($("#lastname").val() == '') {
            $("#lastname").val(data.lastname).prop('readonly', false);
        }
        if ($("#phone").val() == '') {
            $("#phone").val(data.phone).prop('readonly', false);
        }
        if ($("#phone_additional").val() == '') {
            $("#phone_additional").val(data.phone_additional).prop('readonly', false);
        }
        if ($("#name_custom").val() == '') {
            $("#name_custom").val(data.name_custom).prop('readonly', false);
        }
        $("#email").val(data.email).prop('readonly', false);
        $("#nip").removeClass('highlight_red').addClass('highlight_green');
        $("#clientid").val(data.clientid).prop('readonly', false); // previously true
        $("#receiptid").val(data.receiptid).prop('readonly', false);
        $("#orderid").val(data.orderid).prop('readonly', false);
        $("#client_orders").val(data.client_orders).prop('readonly', true); // true
        $("#town").val(data.town).prop('readonly', false);
        $("#postcode").val(data.postcode).prop('readonly', false);
        $("#street").val(data.street).prop('readonly', false);
        $("#house_no").val(data.house_no).prop('readonly', false);
        $("#flat_no").val(data.flat_no).prop('readonly', false);
        $("#type").val(data.type).prop('readonly', false);
        $("#country").val(data.country).prop('readonly', false);
        //$("#name").val(data.name).prop('readonly', false);
        if ($("#name").is('input')) {
            $("#name").val(data.name).prop('readonly', false);
        }

        if ($("#nip").val() !== '') { $("#nip").removeClass('missed'); }
        if ($("#clientid").val() !== '') { $("#clientid").removeClass('missed'); }
        if ($("#receiptid").val() !== '') { $("#receiptid").removeClass('missed'); }
        if ($("#orderid").val() !== '') { $("#orderid").removeClass('missed'); }
        if ($("#client_orders").val() !== '') { $("#client_orders").removeClass('missed'); }
        if ($("#firstname").val() !== '') { $("#firstname").removeClass('missed'); }
        if ($("#lastname").val() !== '') { $("#lastname").removeClass('missed'); }
        if ($("#town").val() !== '') { $("#town").removeClass('missed'); }
        if ($("#postcode").val() !== '') { $("#postcode").removeClass('missed'); }
        if ($("#street").val() !== '') { $("#street").removeClass('missed'); }
        if ($("#house_no").val() !== '') { $("#house_no").removeClass('missed'); }
        if ($("#flat_no").val() !== '') { $("#flat_no").removeClass('missed'); }
        if ($("#type").val() !== '') { $("#type").removeClass('missed'); }
        if ($("#phone").val() !== '') { $("#phone").removeClass('missed'); }
        if ($("#phone_additional").val() !== '') { $("#phone_additional").removeClass('missed'); }
        if ($("#name_custom").val() !== '') { $("#name_custom").removeClass('missed'); }
        if ($("#country").val() !== '') { $("#country").removeClass('missed'); }
        if ($("#name").val() !== '') { $("#name").removeClass('missed'); }
        
        /*"clientid" => $clientid,
        "receiptid" => $receiptid,
        "client_orders" => $client_orders,
        "firstname" => $firstname,
        "lastname" => $lastname,
        "name_custom" => $name_custom,
        "name" => $name,
        "phone" => $phone,
        "street" => $street,
        "house_no" => $house_no,
        "flat_no" => $flat_no,
        "postcode" => $postcode,
        "town" => $town,
        "province" => $province,
        "country" => $country,
        "since" => $since,
        "import_db" => $import_db,
        "clientExists" => $clientExists,*/
    }
}

function noNull(value) {
    return value === null ? '' : value;
}

// Fetch suggestions
function hideAllSuggestions(exceptId) {
    $(".suggestions").each(function() {
        if ($(this).attr('id') !== exceptId + "Suggestions") {
            $(this).hide();
        }
    });
}

/*function modifyNumberPatternAndText(text, action) {
    // Regular expressions to find the pattern
    const pattern = /(\d+\/\d+)(-\d+)?/;
    let lastNumber = text.match(/(\d+\/\d+(-\d+)?)(?!.*\d+\/\d+)/); // Match the last occurrence
    let previousRange = lastNumber ? lastNumber[2] : null;
    let base = lastNumber ? lastNumber[1] : null;

    if (!base) {
        return text; // No matching number found, return original text
    }

    let parts = base.split('-');
    let start = parts[0].split('/')[1];
    let end = parts.length > 1 ? parseInt(parts[1], 10) : parseInt(start, 10);

    // Modify the number based on the action
    if (action === 'increase') {
        end += 1;
    } else if (action === 'decrease') {
        if (end > parseInt(start, 10)) {
            end -= 1;
        } else {
            // If decreasing below the start, return to single number
            text = text.replace(pattern, `${parts[0]}`);
            return text.replace('Twoje części zostały przyjęte', 'Twoja część została przyjęta');
        }
    }

    // Ensure the number is always formatted as three digits
    let formattedEnd = end.toString().padStart(3, '0');
    let newRange = end > parseInt(start, 10) ? `${parts[0]}-${formattedEnd}` : `${parts[0]}`;
    let newText = text.replace(base, newRange);

    // Adjust the text based on whether a range exists
    if (previousRange && !newText.includes('-')) {
        newText = newText.replace('Twoje części zostały przyjęte', 'Twoja część została przyjęta');
    } else if (!previousRange && newText.includes('-')) {
        newText = newText.replace('Twoja część została przyjęta', 'Twoje części zostały przyjęte');
    }

    return { newRange, newText };
}*/

function formFields(type, arrdata, element, elementName) {
    let cform = valueName = readonly = id = multiple = multi = levelQuote = '';
    
    if (elementName == 'Imię' || elementName == 'Nazwisko' || elementName == 'Telefon') {
        cform += '<label for="'+ element +'">'+ elementName +' *</label>';
    } else if (type != 'hidden' && type != 'multiple') {
        let temp_elementName = elementName.replace(/Data zlecenia weryfikacji/g,'Data zlecenia<br>weryfikacji');
        temp_elementName = temp_elementName.replace(/Data zlecenia naprawy/g,'Data zlecenia<br>naprawy');
        cform += '<label for="'+ element +'">'+ temp_elementName +'</label>';
    }

    if (element == 'client_orders') { // || element == 'clientid'
        readonly = 'readonly';
    }

    if (level === 'admin') {
        levelQuote = '<label for="verification_quote">Wycena</label><input placeholder="Wycena weryfikacji" type="text" class="verification_quote" name="verification_quote[]"><input placeholder="Wycena naprawy" type="text" class="repair_quote" name="repair_quote[]">';
    }

    switch (type) {
        case 'select':
            cform += '<select name="'+ element +'" id="'+ element +'">';
                elementNameVal = elementName.replace('Status', '').replace('Metoda zlecenia', '').replace('Metoda odbioru', '').replace('Metoda dostawy', '').replace('Poziom dostępu', '').replace('Płatność', '');
                cform += '<option value="'+ elementNameVal +'">'+ elementName +'</option>';
                arrdata.forEach((value) => { // data.forEach((value, index) => {
                    //valueName = value.replace('admin', 'Administrator').replace('editor', 'Moderator').replace('user', 'Użytkownik').replace('view', 'Podgląd');
                    //cform += '<option value="'+ value +'">'+ valueName +'</option>';
                    valueName = value.replace('Administrator', 'admin').replace('Moderator', 'editor').replace('Użytkownik', 'user').replace('Podgląd', 'view').replace('Metoda zlecenia', '');
                    cform += '<option value="'+ valueName +'">'+ value +'</option>';
                });
            cform += '</select>';
            break;
        case 'checkbox':
            if (element != 'interference') { id = 'id="'+ element +'"'; multi = '' } else { id = ''; multi = '[]'; }
            cform += '<input type="checkbox" ' + id + ' class="'+ element +'_checkbox" name="'+ element + multi + '" value="TAK" '+ arrdata +' >';
            if (element == 'send_sms') { cform += '<div id="sms_counter"><span>1</span> sms <span>160</span> znaków pozostało</div>'; }
            if (element == 'individual') { cform += '<div id="client_exists"></div><input id="client_exists_mark" name="client_exists_mark" type="hidden">'; }
            break;
        case 'textarea':
            if (arrdata == 'multiple') { multiple = '[]'; arrdata = ''; }
            if (element != 'interference_note') { id = 'id="'+ element +'"'; } else { id = ''; }
            cform += '<textarea placeholder="'+ elementName +'" ' + id + ' name="'+ element + multiple + '">'+ arrdata +'</textarea>';
            break;
        case 'number':
            cform += '<input placeholder="'+ elementName +'" type="'+ type +'" id="'+ element +'" name="'+ element +'" min="0">';
            break;
        case 'file':
            cform += '<input type="'+ type +'" class="'+ element +'" name="'+ element +'[]" multiple accept="image/*"><div class="'+ element +'_button">Dodaj (Max 2)</div><div class="'+ element +'_placeholder">&nbsp;</div>';
            break;
        case 'multiple':
            cform += '<div id="duplicate"><div id="editTip"><i class="fa-solid fa-circle-info"></i><p>Jesteś w trybie <strong>EDYCJI</strong> i klikając przycisk "Dodaj" dodasz podzespół do istniejącego zamówienia numer <span></span></p></div><label for="'+ element +'">'+ elementName +'</label><input placeholder="'+ elementName +'" type="text" class="'+ element +'" name="'+ element +'[]" required><input type="hidden" class="'+ element +'_index" name="'+ element +'_index[]"><input type="button" class="add_button" value="Dodaj">'+ levelQuote +'<br>'; // div closed after 'interference note box'
            break;
        default:
            if (arrdata == 'multiple') { multiple = '[]'; arrdata = ''; }
            if (element != 'attachments_path' && element != 'attachments_edit') { id = 'id="'+ element +'"'; } else { id = ''; }
            cform += '<input placeholder="'+ elementName +'" type="'+ type +'" ' + id + ' name="'+ element + multiple + '" value="'+ arrdata +'" '+ readonly +'>';
    }
    
    if (type != 'multiple' && element != 'company' && element != 'attachments_path' && element != 'attachments_edit' && element != 'history' && element != 'user' && element != 'interference' && element != 'individual' && element != 'send_sms_note' && element != 'checkbox_clients' && element != 'checkbox_users' && element != 'checkbox_orders' && element != 'checkbox_companies') {
        cform += '<br>';
    }

    return cform;
}

function fetchRecords(dbTable, company, archived, sortByDate, id = '', scrollMe = false, status = '') {
    $('#loading').show();
    $('#rowCount').hide();
    const startTime = performance.now(); // loadtime
    let deleted = 0;
    if (new URLSearchParams(window.location.search).has('kosz')) { deleted = 1; }
    $.ajax({
        url: 'admin/fetchRecords.php', // URL to your PHP script for fetching records
        type: 'POST',
        dataType: 'json',
        data: {dbTable: dbTable, deleted: deleted, archived: archived, sortByDate: sortByDate},
        success: function(response) {

            //test
            //console.log(response);

            login = response.login; // GLOBAL
            level = response.level; // GLOBAL - for future use

            //console.log(response);
            let cbox = csearch = cnames = cform = html = imDeleted = imArchived = '';
            const records = response.records;
            const ctitles = Object.keys(records[0]); // Get column names but min. 1 record required in the db table
            //const ctitles = Object.keys(records[0]).filter(key => key !== 'deleted'); // Ignore 'deleted' column
            let cboxes = response.checkboxes;

            //if (dbTable == 'orders') {
                if (cboxes[0]==1) { $('#sortByDate').prop('checked', true); } else { $('#sortByDate').prop('checked', false); }
                cboxes = (response.checkboxes).substring(1);
            //}

            // Default for orders form
            clientid_default = response.clientid;
            orderid_default = response.orderid;
            receiptid_default = response.receiptid;
            clientorders_default = response.clientorders;

            for (let i = 0; i < ctitles.length; i++) {
                element = ctitles[i];

                // Replace db original names with user-friendly PL names
                let replacements = {'id':'LP', 'date':'Data', 'firstname':'Imię', 'lastname':'Nazwisko', 'phone':'Telefon', 'phone_additional':'Dodatkowy telefon', 'email':'Email', 'name_custom':'Nazwa własna', 'name':'Nazwa', 'nip':'NIP', 'street':'Ulica', 'house_no':'Numer budynku', 'flat_no':'Numer lokalu', 'postcode':'Kod pocztowy', 'town':'Miejscowość', 'country':'Państwo', 'import_db':'Import z bazy', 'note_receive':'Notatka przyjęcia', 'note_repair':'Notatka naprawy', 'role':'Funkcja', 'regon':'Regon', 'insert_date':'Data utworzenia', 'update_date':'Data edycji', 'clientid':'Numer klienta', 'part_name':'Nazwa części', 'receiptid':'Numer zlecenia', 'orderid':'Numer przyjęcia', 'order_method':'Metoda zlecenia', 'delivery_method':'Metoda dostawy', 'verification_date':'Termin weryfikacji', 'verification_date_start':'Zlecenie weryfikacji', 'verification_quote':'Wycena weryfikacji', 'verification_accepted':'Potwierdzenie weryfikacji', 'repair_date':'Termin naprawy', 'repair_date_start':'Zlecenie naprawy', 'repair_quote':'Wycena naprawy', 'repair_accepted':'Potwierdzenie naprawy', 'status':'Status', 'attachments_path':'Ścieżka załączników', 'attachments':'Zdjęcia', 'history':'Historia zmian', 'archived':'Archiwizuj', 'login':'Login', 'password':'Hasło', 'password_reset_token':'Reset hasła', 'email':'Email', 'checkbox_clients':'Kolumny klienci', 'checkbox_orders':'Kolumny zlecenia', 'checkbox_users':'Kolumny użytkownicy', 'checkbox_companies':'Kolumny firmy', 'level':'Poziom dostępu', 'active':'Aktywny', 'assemble':'Składać', 'interference':'Notatka do podzespołu', 'interference_note':'Treść notatki', 'send_sms':'Wyślij SMS', 'send_sms_note':'Treść wiadomości', 'client_orders':'Ilość realizacji', 'user':'Użytkownik', 'company':'Firma', 'acceptance_date':'Data przyjęcia', 'verification_date_count':'Dni robocze', 'repair_date_count':'Dni robocze', 'deleted':'Usunięte', 'individual':'Osoba fizyczna', 'vatid':'Numer faktury', 'verification_urgent':'Pilna weryfikacja', 'repair_urgent':'Pilna naprawa', 'assemble_ready':'Gotowe do wydania bez naprawy', 'collect_method':'Metoda odbioru', 'payment':'Płatność', 'offersent':'Oferta wysłana'};
                let elementName = element.replace(/\b(id|date|firstname|lastname|phone|phone_additional|email|name_custom|name|nip|street|house_no|flat_no|postcode|town|country|import_db|note_receive|note_repair|role|regon|insert_date|update_date|clientid|part_name|receiptid|orderid|order_method|delivery_method|verification_date|verification_date_start|verification_quote|verification_accepted|repair_date|repair_date_start|repair_quote|repair_accepted|status|attachments_path|attachments|history|archived|login|password|password_reset_token|email|checkbox_clients|checkbox_orders|checkbox_users|checkbox_companies|level|active|assemble|interference|interference_note|send_sms|send_sms_note|client_orders|user|company|acceptance_date|verification_date_count|repair_date_count|deleted|individual|vatid|verification_urgent|repair_urgent|assemble_ready|collect_method|payment|offersent)\b/g, m => replacements[m]);

                if (cboxes[i]==1) { chd = 'checked'; dsp = ''; } else { chd = ''; dsp = 'style="display:none"'; }
                cbox += '<label><input type="checkbox" name="column" value="'+ element +'" '+ chd +'>'+ elementName +'</label>';
                csearch += '<th data-column="'+ element +'" '+ dsp +'><input type="text" class="searchInput" placeholder="Szukaj"></th>'; // &#128269;
                cnames += '<th class="th" data-column="'+ element +'" '+ dsp +'>'+ elementName +'</th>';
                
                if (!['date', 'update_date', 'insert_date', 'deleted', 'checkbox_clients', 'checkbox_orders', 'checkbox_users', 'checkbox_companies', 'street', 'house_no', 'flat_no', 'postcode', 'town', 'country'].includes(element)) {
                    // Customized fields
                    if (element == 'user') {
                        cform += formFields('hidden', login, element, elementName);
                    } else if (element == 'id' || element == 'attachments_path' || element == 'history' || element == 'import_db' || element == 'assemble_ready') {
                        cform += formFields('hidden', '', element, elementName);
                    } else if (element == 'phone') {
                        cform += '<div id="phone_info"><input type="text" value="Upewnij się że jeden z numerów jest poprawny i KOMÓRKOWY" class="warning" disabled=""><br></div>';
                        cform += formFields('text', '', element, elementName);
                    } else if (element == 'company') {
                        cform += formFields('hidden', company, element, elementName);
                    } else if (element == 'receiptid') {
                        // Add extra 'change logo' feature
                        cform += '<img class="logo" src="">';
                        cform += '<a href="#" id="changeCompany2">Zmień Firmę</a>';
                        cform += formFields('hidden', receiptid_default, element, elementName);
                        //cform += formFields('hidden', '', 'highest_receiptid', elementName);
                    } else if (element == 'orderid') {
                        cform += formFields('hidden', orderid_default, element, elementName);
                    } else if (element == 'order_method') {
                        const arrdata = ['Telefonicznie', 'SMS', 'Email', 'Na piśmie'];
                        cform += formFields('select', arrdata, element, elementName);
                    } else if (element == 'payment') {
                        const arrdata = ['Zapłacono', 'Przelew odroczony', 'Brak płatności'];
                        cform += formFields('select', arrdata, element, elementName);
                    } else if (element == 'delivery_method') {
                        const arrdata = ['Osobiście', 'Kurier'];
                        cform += formFields('select', arrdata, element, elementName);
                    } else if (element == 'collect_method') {
                        const arrdata = ['Osobiście', 'Kurier'];
                        cform += formFields('select', arrdata, element, elementName);
                    } else if (element == 'status') {
                        // Used also in orders.js!
                        const arrdata = ['Przyjęto na magazyn', 'Zlecona weryfikacja', 'Rozebrane do wyceny', 'Wydać bez naprawy', 'Wyceniona naprawa', 'Zlecona naprawa', 'Zrobić test', 'Wydać po naprawie', 'Zakończono', 'Reklamacja'];
                        cform += formFields('select', arrdata, element, elementName);
                    } else if (element == 'assemble') { // Hidden checkbox
                        cform += '<div class="checkboxContainer" id="'+ element +'_box" class="'+ element +'_box">';
                            cform += ' <span class="lighterText">Wydać bez naprawy </span>&nbsp;&nbsp;';
                            cform += formFields('checkbox', '', element, elementName);
                            //cform += '<input type="checkbox" id="assemble" name="assemble"><label for="assemble">Składać</label>';
                        cform += '</div>';
                    } else if (element == 'offersent') { // Hidden checkbox
                        cform += '<div class="checkboxContainer" id="'+ element +'_box" class="'+ element +'_box">';
                            cform += ' <span class="lighterText">Wyceniona naprawa </span>&nbsp;&nbsp;';
                            cform += formFields('checkbox', '', element, elementName);
                            //cform += '<input type="checkbox" id="assemble" name="assemble"><label for="assemble">Składać</label>';
                         cform += '</div>';
                    } else if (element == 'verification_date_start') {
                        cform += '<div id="blockVerification">';
                        cform += '<p class="blockTitle">Zlecenie weryfikacji</p>';
                        cform += formFields('date', '', element, elementName);
                    } else if (element == 'repair_date_start') {
                        cform += '<div id="blockRepair">';
                        cform += '<p class="blockTitle">Zlecenie naprawy</p>';
                        cform += formFields('date', '', element, elementName);
                    } else if (element == 'verification_date_count' || element == 'repair_date_count') {
                        cform += formFields('text', '', element, 'Dni robocze');
                    } else if (element == 'verification_date' || element == 'repair_date') {
                        cform += formFields('date', '', element, 'Termin');
                    } else if (element == 'acceptance_date') {
                        cform += formFields('datetime-local', '', element, 'Przyjęto');
                    } else if (element == 'attachments') {
                        cform += formFields('hidden', 'multiple', element + '_edit', elementName);
                        cform += formFields('file', '', element, elementName);
                    } else if (element == 'note_receive' || element == 'note_repair') {
                        cform += formFields('textarea', '', element, elementName);
                    } else if (element == 'interference_note') {
                        cform += '<div class="'+ element +'_box" class="'+ element +'_box">';
                            cform += formFields('textarea', 'multiple', element, 'Notatka');
                        cform += '</div>';
                        cform += '</div>'; // closing 'duplicate' box
                    } else if (element == 'send_sms_note') {
                        cform += '<div id="'+ element +'_box">';
                            let ta_text = 'Twoja część została przyjęta na magazyn pod numerem ' + orderid_default + '. Zapoznaj się z ogólnymi warunkami umów. Nie odpowiadaj na tę wiadomość.';
                            cform += formFields('textarea', ta_text, element, elementName);
                        cform += '</div>';
                        cform += formFields('checkbox', '', 'client_address', 'Dane adresowe');
                        // ADDRESS
                        cform += '<div id="client_address_box">';
                            cform += '<label for="street">Ulica</label><input placeholder="Ulica" type="text" id="street" name="street" value=""><br>';
                            cform += '<label for="house_no">Numer budynku</label><input placeholder="Numer budynku" type="text" id="house_no" name="house_no" value=""><br>';
                            cform += '<label for="flat_no">Numer lokalu</label><input placeholder="Numer lokalu" type="text" id="flat_no" name="flat_no" value=""><br>';
                            cform += '<label for="postcode">Kod pocztowy</label><input placeholder="Kod pocztowy" type="text" id="postcode" name="postcode" value=""><br>';
                            cform += '<label for="town">Miejscowość</label><input placeholder="Miejscowość" type="text" id="town" name="town" value=""><br>';
                            cform += '<label for="country">Państwo</label><input placeholder="Państwo" type="text" id="country" name="country" value=""></input>';
                        cform += '</div>';
                    } else if (element == 'archived' || element == 'interference' || element == 'verification_urgent' || element == 'repair_urgent' || element == 'individual') {
                        cform += '<div class="checkboxContainer">';
                            cform += formFields('checkbox', '', element, elementName);
                        cform += '</div>';
                        if (element != 'interference') {
                            cform += '</div>';
                        }
                    } else if (element == 'verification_accepted' || element == 'repair_accepted') {
                        cform += '<div class="checkboxContainer confirmDeadline" style="display:none; color:red">';
                            cform += formFields('checkbox', '', 'deadline_confirmation', '<div style="color:red"><strong>UWAGA!</strong> Już <span>jest (1) termin</span> na ten dzień - potwierdź datę</div>');
                        cform += '</div>';
                        cform += '<div class="checkboxContainer">';
                            cform += formFields('checkbox', '', element, elementName);
                        cform += '</div>';
                    } else if (element == 'verification_quote' || element == 'repair_quote') {
                        //cform += formFields('number', '', element, 'Wycena');
                    } else if (element == 'part_name') {
                        cform += formFields('multiple', '', element, elementName);
                    // users table
                    } else if (element == 'level') {
                        const arrdata = ['Administrator', 'Moderator', 'Użytkownik', 'Podgląd'];
                        cform += formFields('select', arrdata, element, elementName);
                    } else if (element == 'active') {
                        cform += formFields('checkbox', 'checked', element, elementName);
                    } else if (element == 'send_sms') {
                        cform += formFields('checkbox', '', element, elementName);
                    } else if (element == 'name' || element == 'surname') {
                        cform += formFields('text', '', element, elementName);
                        cform += '<div id="' + element + 'Suggestions" class="suggestions"></div>'; // Search results
                    } else if (element == 'clientid') {
                        cform += formFields('text', clientid_default, element, elementName);
                    } else if (element == 'client_orders') {
                        cform += formFields('text', clientorders_default, element, elementName);
                    } else if (element == 'login') {
                        cform += formFields('text', '', element, elementName);
                        cform += formFields('text', '', 'password', 'Hasło');
                        cform += formFields('hidden', '', 'password_original', 'Hasło oryginalne');
                        cform += formFields('hidden', '11111111111111111111', 'checkbox_clients', 'Kolumny klienci');
                        cform += formFields('hidden', '111111111111111111111111111111111111111111111', 'checkbox_orders', 'Kolumny zlecenia');
                        cform += formFields('hidden', '1111111111', 'checkbox_users', 'Kolumny klienci');
                        cform += formFields('hidden', '1111111111111111111', 'checkbox_companies', 'Kolumny klienci');
                    } else {
                        cform += formFields('text', '', element, elementName);
                    }

                }
            }
            csearch += '<th><div id="resetSearch"><a href="#">Reset</a></div></th>';
            cnames += '<th>Opcje</th>';
            cform += '<br><button type="button" id="saveBtn">Zapisz</button><button type="button" class="cancelBtn">Anuluj</button>';
            
            const orderOptions = {
                'Przyjęto na magazyn': 7,
                'Zlecona weryfikacja': 3,
                'Rozebrane do wyceny': 10,
                'Wydać bez naprawy': 5,
                'Wyceniona naprawa': 8,
                'Zlecona naprawa': 2,
                'Zrobić test': 4,
                'Wydać po naprawie': 6,
                'Zakończono': 9,
                'Reklamacja': 1
            };

            // Count archived
            let i = 0;
            records.forEach(function(record) {
                i++;
                if (i > 100) {
                    hideMe = 'hideMe';
                } else if (i == 100) {
                    hideMe = 'loadMe';
                } else {
                    hideMe = '';
                }

                if (record.deleted == '1' || record.deleted == 'TAK') { imDeleted = 'deleted'; } else { imDeleted = ''; }
                if (record.archived == '1' || record.archived == 'TAK') { imArchived = 'archived'; } else { imArchived = ''; }
                
                let statusKey = orderOptions[record.status];
                
                //let rowHtml = `<tr id="id_${record.id}" class="status-${statusKey} ${imDeleted} ${imArchived}">`;
                let rowHtml = `<tr id="id_${record.id}" class="${imDeleted} ${imArchived} ${hideMe}">`;

                Object.values(record).forEach(function(value, index) {
                    // Replace 'null' with an empty string
                    value = value === null ? '' : value;

                    let ctitle = ctitles[index % ctitles.length];
                    let statusClass = '';
                    dsp = (cboxes[index % cboxes.length] == 0) ? 'style="display:none"' : '';
                    if (ctitle == 'attachments') {
                        if (value == '') {
                            value = '';
                        } else {
                            let split = value.split(',');
                            let photoword = split.length == 1 ? ' zdjęcie' : (split.length < 5 ? ' zdjęcia' : ' zdjęć');
                            value = `<a class="showPhotos" href="${record.attachments_path}/${value}">${split.length}${photoword}</a>`;
                        }
                    } else if (ctitle == 'id' || ctitle == 'status') {
                        statusClass = ' class="status-' + statusKey + '"';
                    }

                    if (ctitle == 'status') {
                        rowHtml += `<td data-column="${ctitle}" ${statusClass} ${dsp}>
                            <select name="status" data-id="${record.id}" ${statusClass}>
                                <option value="Status">Status</option>`;

                                for (const [optionValue, optionNumber] of Object.entries(orderOptions)) {
                                    rowHtml += `<option value="${optionValue}"${optionValue === value ? ' selected' : ''}>${optionValue}</option>`;
                                }

                            rowHtml += `</select>
                        </td>`;
                    } else if (ctitle == 'level') {
                        let valueR = value.replace('admin', 'Administrator').replace('editor', 'Moderator').replace('user', 'Użytkownik').replace('view', 'Podgląd');
                        rowHtml += `<td data-column="${ctitle}" ${statusClass} ${dsp}>${valueR}</td>`;
                    } else {
                        rowHtml += `<td data-column="${ctitle}" ${statusClass} ${dsp}>${value}</td>`;
                    }
                });

                rowHtml += `<td>`;
                    rowHtml += `<div class="action-buttons">`;
                        if (dbTable == 'orders') {
                            rowHtml += `<button data-title="&larr; Drukuj" data-type="doc" class="printBtn showPrint" data-id="${record.id}">Drukuj</button>`;
                            rowHtml += `<div class="printBox">`;
                                rowHtml += `<button data-title="Dokument przyjęcia" data-type="doc" class="printBtn" data-id="${record.id}">Drukuj</button>`;
                                rowHtml += `<button data-title="Dokument weryfikacji" data-type="doc2" class="printBtn" data-id="${record.id}">Drukuj</button>`;
                                rowHtml += `<button data-title="Dokument naprawy" data-type="doc3" class="printBtn" data-id="${record.id}">Drukuj</button>`;
                                rowHtml += `<button data-title="Dokument wydania" data-type="doc4" class="printBtn" data-id="${record.id}">Drukuj</button>`;
                                rowHtml += `<button data-title="Adres" data-type="address" class="printBtn" data-id="${record.id}">Drukuj</button>`;
                                rowHtml += `<button data-title="Etykieta" data-type="label" class="printBtn" data-id="${record.id}">Drukuj</button>`;
                            rowHtml += `</div>`;
                            //rowHtml += `<br>`;
                        }
                        if (dbTable == 'orders' && imDeleted == 'deleted') {
                            rowHtml += `<button data-title="Przywróć" class="resBtn" data-id="${record.id}">Przywróć</button>`;
                            rowHtml += `<button data-title="Usuń" class="desBtn" data-id="${record.id}">Usuń</button>`;
                        } else {
                            rowHtml += `<button data-title="Edytuj" class="editBtn" data-id="${record.id}">Edytuj</button>`;
                            
                            if (dbTable == 'orders') {
                                rowHtml += `<button data-title="Dodaj zdjęcie" class="photoBtn" data-id="${record.id}">Dodaj zdjęcie</button>`;
                                rowHtml += `<button data-title="Pokaż w Panelu" class="switchBtn" data-id="${record.id}">Pokaż w Panelu</button>`;
                            }

                            if (dbTable == 'orders' && imArchived != 'archived' && imDeleted != 'deleted') {
                                rowHtml += `<button data-title="Archiwizuj" class="arcBtn" data-id="${record.id}">Archiwizuj</button>`;
                            }
                            rowHtml += `<button data-title="Usuń" class="delBtn" data-id="${record.id}">Usuń</button>`;
                        }
                    rowHtml += `</div>`;
                rowHtml += `</td>`;

                rowHtml += `</tr>`;
                html += rowHtml;
                //<td>${record.name} ${record.surname}</td>
                /*html += `<tr>
                    <td data-column="id">${record.id}</td>
                    <td><button class="editBtn" data-id="${record.id}">Edit</button></td>
                </tr>`;*/
            });

            $('#dropdownContent').html(cbox); //columnToggles
            $('#columnSearch').html(csearch);
            $('#recordsTable tbody').html(html);
            $('#columnNames').html(cnames);

            // TEST
            // 1.85s 8000 records
            // 0.25s no record loaded
            // 0.50s no record displayed (loaded with display:none)

            if (dbTable == 'orders') {
                cform += '<div id="learn" data-title="Samouczek"><i class="fa-solid fa-circle-info"></i></div>';
            }

            $('#recordForm').html(cform);

            // Tips
            let text;
            if (dbTable == 'users') {
                // Level
                text = '<strong>Administrator:</strong> pełny dostęp<br><strong>Moderator:</strong> jak admin ale nie widzi cen ani baz danych<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"Klienci", "Użytkownicy", "Firmy"<br><strong>Użytkownik:</strong> dostęp tylko do Panelu Zleceń z możliwością zmiany<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;statusów i dat weryfikacji/naprawy łącznie z cenami<br><strong>Podgląd:</strong> dostęp tylko do Panelu Zleceń bez możliwości edycji<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;czegokolwiek (głównie dla monitora na hali)';
                $('#recordForm').find('label[for="level"]').before('<div class="editTip2 block"><i style="margin-bottom:105px" class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');
            } else if (dbTable == 'orders') {
                // NIP
                text = 'Pola z lupą służą do wyszukiwania klientów w lokalnej bazie i GUS<br>Zaznacz "osoba fizyczna" dla klientów którzy nie są firmami';
                $('#recordForm').find('label[for="nip"]').before('<div class="editTip2 top40"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');
            
                // Name / Nazwa
                text = 'Wpisz nazwę aby wyświetlić istniejących klientów z lokalnej bazy danych, po czym wybierz z listy LUB wpisz nową nazwę';
                $('#recordForm').find('label[for="name"]').before('<div class="editTip2"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');
            
                // Name_custom / Nazwa własna
                text = 'Nazwa własna czyli skrócona forma nazwy używana jest np. na etykiecie / naklejce';
                $('#recordForm').find('label[for="name_custom"]').before('<div class="editTip2"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');
            
                // Firstname / Imię
                text = 'Gdy klient istnieje już w lokalnej bazie ale zostanie zmienione imię lub nazwisko, zostanie dodany nowy wpis (oddział) do bazy klientów';
                $('#recordForm').find('label[for="firstname"]').before('<div class="editTip2"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');
            
                // Phone / Telefon
                text = 'Jeśli zmienisz tu telefon lub email, pamiętaj że zostanie on również uaktualniony w lokalnej bazie "Klienci"';
                $('#recordForm').find('label[for="phone"]').before('<div class="editTip2"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');

                // Clientid / Numer klienta
                text = 'Dane w polach z zębatkami (dwa pola poniżej) są generowane automatycznie i nieedytowalne';
                $('#recordForm').find('label[for="clientid"]').before('<div class="editTip2"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');

                // Client_orders / Ilość realizacji
                text = 'Do ilości realizacji wliczane są tylko zakończone naprawy';
                $('#recordForm').find('label[for="client_orders"]').before('<div class="editTip2"><i class="single-line fa-solid fa-circle-info"></i><p>' + text + '</p></div>');
            
                // Note / Notatka
                text = 'OGÓLNA notatka, NIE dla poszczególnych podzespołów. Pojawia się np. na dokumencie przyjęcia na magazyn pt. "Uwagi"';
                $('#recordForm').find('label[for="note"]').before('<div class="editTip2"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');
            
                // Send_sms / Wyślij SMS
                text = 'Po zaznaczeniu system wysyła SMS na numer "Telefon" lub w razie np. tel. stacjonarnego na numer "Dodatkowy telefon"';
                $('#recordForm').find('label[for="send_sms"]').before('<div class="editTip2"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');
            
                // Client_address / Dane adresowe
                text = 'Po zaznaczeniu zobaczysz adres klienta ściągnięty z bazy GUS lub lokalnej bazy danych w przypadku klienta indywidualnego';
                $('#recordForm').find('label[for="client_address"]').before('<div class="editTip2"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');

                // Verification_quote / Wycena
                if (level === 'admin') {
                    text = 'Po wpisaniu jednej z wycen pojawi się dodatkowy blok z datami. Przy kwocie naprawy status zmieni się na "Wyceniona naprawa"';
                    $('#recordForm').find('label[for="verification_quote"]').before('<div class="editTip2"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');
                }
            
                // Attachments / Zdjęcia
                // Interference / Ingerencja
                text = 'Możesz dodać max 2 zdjęcia naraz ale potem dodawać kolejne 2 itd. Akceptowalne są tylko .png, .jpg i .gif';
                $('#recordForm').find('label[for="attachments"]').before('<div class="editTip2"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');
                text = 'Pole "Notatka do podzespołu" pojawia się np. w Panelu zleceń pod ikonką "<img style="width:14px; margin-bottom:-2px" src="images/warning.png">"';
                $('#recordForm').find('label[for="attachments"]').before('<div class="editTip2"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');
            
                // Status / Status
                text = 'Po wybraniu "Wydać bez naprawy" pojawi się checkbox "Składać" a po wybraniu "Zakończono" zlecenie zostanie zarchiwizowane';
                $('#recordForm').find('label[for="status"]').before('<div class="editTip2"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');
            
                // Archived / Zarchiwizuj
                text = 'Po zaznaczeniu opcji "Archiwizuj" zlecenie zostanie przeniesione do archiwum niezależnie od aktualnego statusu';
                $('#recordForm').find('label[for="archived"]').before('<div class="editTip2"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');
            
                // Print window / Okno drukuj
                text = 'Jeżeli dodajesz nowe zlecenie, w kolejnym kroku wyświetli się okno "Drukuj" i tylko tam będzie możliwość zapisania wersji PDF';
                $('#recordForm').find('#saveBtn').before('<div class="editTip2"><i class="fa-solid fa-circle-info"></i><p>' + text + '</p></div>');

                //$('.editTip2').show(); // default
            }

            // Make sections in the popup form - add margin-bottom for selected fields
            if (dbTable == 'orders') {
                //$('#nip, #vatid, .blockTitle, label[for="nip"], label[for="vatid"]').css('margin-top','40px');
                $('#vatid, .blockTitle, label[for="vatid"], #nip, label[for="nip"], .top40').css('margin-top','40px');
            } else {
                $('#nip, .blockTitle, label[for="nip"]').css('margin-top','0');
                $('#street, label[for="street"]').css('margin-top','40px');
                $('#note, label[for="note"], #login, label[for="login"]').css('margin-top','20px');
            }

            applySortOrder(); // Apply the last known sort order after rendering
            applySearchFilter(); // New function call to reapply search filters after fetching

            $('#loading').hide();
            $('#rowCount').show();

            adjustTableMargin();

            if (archived == '1') {
                $('tbody tr').hide();
                $('tbody tr.archived').show();
            } else {
                $('tbody tr').show();
                $('tbody tr.archived').hide();
            }
            updateRowCount(dbTable);

            // Show updated row
            if (scrollMe && id != '') {
                //setTimeout(function() { // let the table load
                    let barHeight;
                    if ($('#columnNames').is(':visible')) {
                        barHeight = $('#columnNames').height(); //offsetHeight
                    } else {
                        barHeight = 150;
                    }
                    $('html, body').animate({
                        scrollTop: $('#id_' + id).offset().top - barHeight,
                        scrollLeft: 0
                    }, 800, function() {
                        blinkRow(id, 2, 250);
                        $('#id_' + id).addClass('highlight_blue');
                    });
                //}, 1000);
            }

            // loadtime
            const endTime = performance.now();
            const loadTime = (endTime - startTime) / 1000;
            console.log(`Data loadtime: ${loadTime} seconds`);
        },
        error: function(xhr, status, error) {
            console.error("Error fetching records: ", error);
        }
    });
}

function fetchRecordDetails(id, dbTable, photoMode = false) {
    $('#editTip').show();
    $('#recordForm').css('visibility', 'visible');
    $('input, select').removeClass('missed'); // Reset red fields
    $('#nip').removeClass('highlight_green highlight_red'); // Reset NIP
    $('#client_exists').text('');
    $('.part_name').not('.part_name:first').hide();
    $('.part_name:first').css({'max-width': '337px', 'margin-right': '5px'});
    //$('.duplicate').hide();
    $('.duplicate').remove();
    $('.notification_bg_white').hide();
    $.ajax({
        url: 'admin/fetchRecordDetails.php', // URL to your PHP script for fetching a single record's details
        type: 'POST',
        dataType: 'json',
        data: {id: id, dbTable: dbTable},
        success: function(response) {
            //console.log(response);
            const record = response.record;

            if (dbTable == 'events') {
                $('#cal_date_from').val(record['date_from']);
                $('#cal_date_to').val(record['date_to']);
                $('#cal_event').val(record['event']);
                $('#cal_note').val(record['note']);
                $('#cal_user').val(record['user']);
            } else {
                const ctitles = Object.keys(record);
    
                if ((record['verification_date_count'] == null || record['verification_date_count'] == '') &&
                    (record['verification_date'] == null || record['verification_date'] == '')) {
                    record['verification_date_start'] = null;
                }
                if ((record['repair_date_count'] == null || record['repair_date_count'] == '') &&
                    (record['repair_date'] == null || record['repair_date'] == '')) {
                    record['repair_date_start'] = null;
                }
    
                for (let i = 0; i < ctitles.length; i++) {
                    let element = ctitles[i];
                    // Check a checkbox
                    if (($('#' + element).is(':checkbox') || $('input[name^="' + element + '"]').is(':checkbox')) && 
                        (record[element] == 'TAK' || record[element] == '1')) {
                        $('#' + element).prop('checked', true);
                        $('input[name^="' + element + '"]').prop('checked', true);
                        if (element == 'interference') {
                            $('.' + element + '_note_box').show();
                        }
                    } else if (($('#' + element).is(':checkbox') || $('input[name^="' + element + '"]').is(':checkbox')) && 
                        (record[element] == '' || record[element] === null)) {
                        $('#' + element).prop('checked', false);
                        $('input[name^="' + element + '"]').prop('checked', false);
                    } else if (element == 'attachments') {
                        // attachments and other array-type fields
                        $('input[name="attachments_edit[]"]').val(record[element]);
                        $('input[name="attachments_path"]').val(record['attachments_path']);
                        $('textarea[name="interference_note[]"]').val(record['interference_note']);
                        $('.part_name').val(record['part_name']);
                    } else if (element == 'verification_quote') {
                        $('.' + element).val(record[element]);
                        if (record[element] == '' || record[element] === null) { 
                            $('#blockVerification').hide(); 
                        } else {
                            $('#blockVerification').show(); 
                        }
                    } else if (element == 'repair_quote') {
                        $('.' + element).val(record[element]);
                        if (record[element] == '' || record[element] === null) { 
                            $('#blockRepair').hide();
                        } else {
                            $('#blockRepair').show(); 
                        }
                    } else if (element == 'verification_date_start' && (record[element] === null || record[element] == '')) {
                        $('#verification_date_start').val(currentDateTime('date'));
                    } else if (element == 'repair_date_start' && (record[element] === null || record[element] == '')) {
                        $('#repair_date_start').val(currentDateTime('date'));
                    } else if (element == 'orderid') {
                        $('#orderid').val(record[element]);
                        $('#editTip span').text(record[element]);
                    } else if (element == 'password') {
                        $('#' + element).val(record[element]);
                        $('#' + element + '_original').val(record[element]);
                    } else if (element != 'attachments') {
                        $('#' + element).val(record[element]);
                        if (element == 'company') {
                            $('#recordForm .logo').attr('src', 'images/' + record[element] + '.png');
                        }
                    }
                    //console.log(element + ' :: ' + record[element]);
    
                    if ($('#status').val() === 'Wydać bez naprawy') {
                        $('#assemble_box').show();
                    } else {
                        $('#assemble_box').hide();
                    }
    
                    if ($('#status').val() === 'Wyceniona naprawa' || $('#status').val() === 'Zlecona naprawa' || $('#status').val() === 'Zrobić test' || $('#status').val() === 'Wydać po naprawie' || $('#status').val() === 'Reklamacja') {
                        $('#offersent_box').show();
                    } else {
                        $('#offersent_box').hide();
                    }
                }
    
                //$('#kind').val(record.kind);
                //$('#overlay, #formPopup').fadeIn();
    
                //nip_val = name_val = ''; // reset when using 'osoba fizyczna' checkbox
                nip_val = $('#nip').val();
                //name_val = $('#name').val();
                if ($('#individual').is(':checked')) {
                    $('#nip').prop('disabled', true);
                    $('#nip').attr('placeholder', '').val('').css('font-weight', 'normal');
                    $('#nip, label[for="nip"]').css('opacity', '.5');
                } else if ($('#individual').is(':not(:checked)')) {
                    $('#nip').prop('disabled', false);
                    $('#nip').attr('placeholder', 'NIP').val(nip_val).css('font-weight', 'bold');
                    //$('#name').attr('placeholder', 'Nazwa').val(name_val);
                    $('#nip, label[for="nip"]').css('opacity', '1');
                }
    
                // Get status for comparison when save data
                historyStatus = $('#status').val();
    
                // sms length
                if ($('#send_sms_note').length > 0) {
                    let charCount = [...$('#send_sms_note').val()].reduce((acc, char) => acc + (/[ąćęłńóśźż]/.test(char) ? 2 : 1), 0);
                    $('#sms_counter span').eq(1).text(160 - charCount);
    
                    $('#send_sms').prop('disabled', true);
                    $('#send_sms_note').prop('disabled', true);
                    $('#send_sms_note_box, #sms_counter, label[for="send_sms"]').css('opacity', '0.5')
                }
                
                // EDIT MODE vs PHOTO MODE
                if (photoMode == false) {
                    popupPosition();
                    $('#overlay, #formPopup').fadeIn();
                } else {
                    //$('.attachments').click();
                    /*$('.attachments').attr('capture', 'camera').click();
                    setTimeout(function() {
                        $('.attachments').removeAttr('capture');
                    }, 100);*/
                    $('html, body').animate({scrollTop: 0, scrollLeft: 0}, 800, function() {
                        // option to display form after scroll
                    });
                    $('.attachments').change(function() {
                        saveRecord(dbTable, company, true);
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            console.error("Error fetching record details: ", error);
        }
    });
}

function fetchStatistics() {
    $.ajax({
        url: 'admin/statistics.php', // URL to your PHP script for fetching a single record's details
        type: 'POST',
        //dataType: 'json',
        //data: {id: id, dbTable: dbTable},
        success: function(response) {
            $('#statsContent').html(response);
        },
        error: function(xhr, status, error) {
            console.error("Error fetching record details: ", error);
        }
    });
}

function deleteRecord(id, dbTable, type, company) {
    //console.log(id + ' ' + dbTable + ' ' + type + ' ' + company);
    $.ajax({
        url: 'admin/deleteRecord.php', // URL to your PHP script for fetching a single record's details
        type: 'POST',
        dataType: 'json',
        data: {id: id, dbTable: dbTable, type: type},
        success: function(response) {
            //console.log(response);
            if (dbTable == 'events') {
                $('.event-summary-row').has('img[data-id="' + id + '"]').hide();
            } else if ((new URLSearchParams(window.location.search).has('kosz') && type == 'destroy') ||
                (!new URLSearchParams(window.location.search).has('kosz') && type == 'delete')) {
                $('#id_' + id).fadeOut();
            } else if (new URLSearchParams(window.location.search).has('kosz') && type == 'delete') {
                $('#id_' + id).addClass('deleted');
                $('#id_' + id).find('.editBtn').removeClass('editBtn').addClass('resBtn');
                $('#id_' + id).find('.delBtn').removeClass('delBtn').addClass('desBtn');
            } else if (type == 'restore') {
                $('#id_' + id).removeClass('deleted');
                $('#id_' + id).find('.resBtn').removeClass('resBtn').addClass('editBtn');
                $('#id_' + id).find('.desBtn').removeClass('desBtn').addClass('delBtn');
            } else {
                // Photo delete

                // Method #1: Slide up/down
                /*$('#showPhotos').find('img[src="' + type[0] + '"]').animate({
                    'margin-top': '-4000px'
                }, 500, function() {
                    $('#showPhotos').find('img[src="' + type[0] + '"]').attr('src', 'images/deleted.png').animate({
                        'margin-top': '0'
                    }, 500);
                });*/

                // Method #2: Shrink/grow
                /*$('#showPhotos').find('img[src="' + type[0] + '"]').slideUp(250, function() {
                    $(this).attr('src', 'images/deleted.png').slideDown(250);
                });*/

                // Method #3: Fade Out/in
                /*$('#showPhotos').find('img[src="' + type[0] + '"]').fadeOut(250, function() {
                    $(this).attr('src', 'images/deleted.png').fadeIn(250);
                });*/

                // Method #4: Instant
                $('#showPhotos').find('img[src="' + type[0] + '"]').attr('src', 'images/deleted.png');
                $('#deletePhoto').hide();
            }
            if (dbTable != 'events') {
                fetchRecords(dbTable, company, archived, sortByDate);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error fetching record details: ", error);
        }
    });
}

function printLabel(id, dbTable, method, company, single, pdf) {
    //console.log(single);
    $.ajax({
        url: 'admin/fetchPrint.php',
        type: 'POST',
        dataType: 'json',
        data: {id: id, dbTable: dbTable, method: method, company: company, single: single, pdf: pdf},
        success: function(response) {
            
            var iframe = $('#printFrame');
            
            // Create a Blob and set it as source of the iframe
            var blob = new Blob([response.html], {type: 'text/html'});
            var url = URL.createObjectURL(blob);
            iframe.attr('src', url);
            
            // Print when iframe loads the blob content
            iframe.on('load', function() {
                this.contentWindow.print();
                URL.revokeObjectURL(url);  // Clean up the blob url
            });
            
            // Force PDF download
            if (pdf == 'pdf') {
                forceFileDownload(response.path);
            }
            
        },
        error: function(xhr, status, error) {
            console.error('Error:', status, error);
        }
    });
}

function forceFileDownload(filePath) {
    fetch(filePath)
        .then(response => response.blob())
        .then(blob => {
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);

            link.href = url;
            link.download = filePath.split('/').pop();
            document.body.appendChild(link);
            
            link.click();
            document.body.removeChild(link);

            URL.revokeObjectURL(url);
        })
        .catch(error => console.error('Error downloading file:', error));
}

function writeAndPrint(iframe, iframeDoc, htmlContent) {
    iframeDoc.open();
    iframeDoc.write(htmlContent);
    iframeDoc.close();

    let xiframe = document.getElementById('printFrame').innerHTML;
    //console.log(xiframe);

    // Trigger print for iframe content
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
}

function saveRecord(dbTable, company, photoMode = false) {
    if (photoMode) {
        popupPosition();
        $('#overlay, #formPopup').fadeIn();
        $('#recordForm').css('visibility', 'hidden');
    }

    let formData = new FormData();
    let filePaths = [];
    let sendIt = 1;
    const formElements = document.querySelectorAll('#recordForm input, #recordForm select, #recordForm textarea'); // #recordForm textarea
    const formElements_enabled = Array.from(formElements).filter(input => input.tagName !== 'INPUT' || !input.disabled);

    let verificationDate = '';
    let verificationCount = '';
    let repairDate = '';
    let repairCount = '';

    // Check how many parts to add - if more than one and the first one is empty then ignore it
    if (//input.name == 'archived' && 
        document.querySelectorAll('.part_name').length > 1 && $('#duplicate').find('.part_name').val() === '') {
        //$('#duplicate').find('.part_name').removeClass('missed');
        $('#duplicate').find('input:not(.add_button), select, textarea').prop('disabled', true);
        $('#duplicate').css('opacity', '0.5');
        //console.log(document.querySelectorAll('.part_name').length);
        //console.log($('#duplicate').find('.part_name').val());
    }

    // If NIP field is empty then automatically mark client as 'individual'
    if ($('#nip').val() === '') {
        nip_val = $('#nip').val();
        $('#nip').prop('disabled', true);
        $('#nip').attr('placeholder', '').val('').css('font-weight', 'normal');
        $('#nip, label[for="nip"]').css('opacity', '.5');
        $('#individual').prop('checked', true);
    }
    
    // Verification/repair dates FIX #1
    // Don't save verification_date_start if other two date columns are empty
    formElements.forEach(input => {
        if (input.name === 'verification_date') {
            verificationDate = input.value;
        } else if (input.name === 'verification_count') {
            verificationCount = input.value;
        }
        if (input.name === 'repair_date') {
            repairDate = input.value;
        } else if (input.name === 'repair_count') {
            repairCount = input.value;
        }
    });
 
    formElements_enabled.forEach(input => {
        if (input.type === 'checkbox') {
            if (!input.checked) {
                input.value = '';
            } else {
                input.value = 'TAK';
            }
        }

        // Verification/repair dates FIX #1
        if (verificationDate === '' && verificationCount === '' && input.name === 'verification_date_start') {
            input.value = null; 
        }
        if (verificationDate === '' && verificationCount === '' && input.name === 'verification_date_start') {
            input.value = null; 
        }
        if (repairDate === '' && repairCount === '' && input.name === 'repair_date_start') {
            input.value = null; 
        }
        if (repairDate === '' && repairCount === '' && input.name === 'repair_date_start') {
            input.value = null; 
        }

        formData.append(input.name, cleanSpaces(input.value));
        // let value = (input.value == '') ? null : input.value; // Replace empty with NULL
        if (dbTable == 'orders' && 
            (
            (input.name == 'individual' && document.querySelector("input[name='individual']").checked) || 
            (input.name == 'phone_additional' && input.value == '') ||
            (input.name == 'nip' && input.value == '') ||
            (input.name == 'street' && input.value == '') ||
            (input.name == 'house_no' && input.value == '') ||
            (input.name == 'flat_no' && input.value == '') ||
            (input.name == 'postcode' && input.value == '') ||
            (input.name == 'town' && input.value == '') ||
            (input.name == 'country' && input.value == '') ||
            (input.name == 'verification_date_start' && input.value == '') ||
            (input.name == 'repair_date_start' && input.value == '') ||
            (input.name == 'verification_quote' && input.value == '') ||
            (input.name == 'repair_quote' && input.value == '') ||
            (input.name == 'order_method' && input.value == '') ||
            (input.name == 'assemble_ready' && input.value == '') ||
            (input.name == 'collect_method' && input.value == '') ||
            (input.name == 'payment' && input.value == '')
            )) {
            // Ignore
        } else if (dbTable == 'orders' && 
            (
            (input.tagName === 'SELECT' && input.selectedIndex === 0 && (input.name !== 'order_method' && input.name !== 'collect_method' && input.name !== 'payment')) || // select not selected
            (input.value == '' && input.tagName != 'TEXTAREA' && input.name !== 'user' && input.name !== 'history' && input.name !== 'verification_quote[]' && input.name !== 'repair_quote[]' && // input field except few specific ones
            input.name !== 'flat_no' && input.name !== 'verification_date_count' && input.name !== 'verification_date' && input.name !== 'verification_quote' && 
            input.name !== 'repair_date_count' && input.name !== 'repair_date' && input.name !== 'repair_quote' && input.name !== 'vatid' && input.name !== 'email' && 
            input.type !== 'hidden' && input.type !== 'file' && input.type !== 'checkbox') ||
            ($('#status').val() === 'Zlecona weryfikacja' && $("#blockVerification").is(":visible") && (input.name == 'verification_date_count' && input.value == '')) ||
            ($('#status').val() === 'Zlecona weryfikacja' && $("#blockVerification").is(":visible") && (input.name == 'verification_date' && input.value == '')) ||
            ($('#status').val() === 'Zlecona weryfikacja' && $("#blockVerification").is(":visible") && (input.name == 'verification_start' && input.value == '')) ||
            ($('#status').val() === 'Zlecona naprawa' && $("#blockRepair").is(":visible") && (input.name == 'repair_date_count' && input.value == '')) ||
            ($('#status').val() === 'Zlecona naprawa' && $("#blockRepair").is(":visible") && (input.name == 'repair_date' && input.value == '')) ||
            ($('#status').val() === 'Zlecona naprawa' && $("#blockRepair").is(":visible") && (input.name == 'repair_start' && input.value == ''))
            )) {
            //console.log(input);
            $(input).addClass('missed');
            sendIt = 0;
        } else {
            $(input).removeClass('missed');
        }

        // Check how many parts to add - if more than one and the first one is empty then ignore it
        if (input.name == 'archived' && 
            document.querySelectorAll('.part_name').length > 1 && $('#duplicate').find('.part_name').val() === '') {
            $('#duplicate').find('.part_name').removeClass('missed');
        }

        // Add to history only if status changed
        if (input.name == 'status' && input.value != historyStatus) {
            historySave = 1;
        } else if (input.name == 'status' && input.value == historyStatus) {
            historySave = '';
        }
    });

    // Stop if there are missed input fields in the form
    //console.log(sendIt);
    if (sendIt == 1) {

        // Check if this is update or new
        let id = $('#id').val();
        let isUpdate = false;
        if (id !== null && id !== '') {
            isUpdate = true;
        }

        formData.append('dbTable', dbTable); // Append the dbTable value to the FormData object

        if (dbTable == 'orders') {
            formData.append('history', historySave);
            // Super important for passing uploaded files!
            let j = $('#client_orders').val();
            let nextDir = Number($('#receiptid').val().replace('/',''));
            s = 0;
            $('.attachments').each(function(index) {
                let files = this.files;
                if (!isUpdate) {
                    nextDir = nextDir + 1; 
                }
                j++
                if (files.length > 0) {
                    let baseName = index === 0 ? 'attachments[]' : `attachments_${index + 1}[]`;
                    for (let i = 0; i < files.length; i++) {
                        formData.append(baseName, files[i]);
                        // Filelist
                        s = window.s + files[i].size; // File size
                        let fileI = files[i].name;
                        let lastIndex = fileI.split('.');
                        let fileTmp = lastIndex[lastIndex.length - 1];
                        let fileFull = (i + 1) + '.' + fileTmp;
                        //let pathTmp = j.toString().padStart(3, '0');
                        let pathFull = '../uploads/' + nextDir + '/' + fileFull; // Full path
                        filePaths.push(pathFull);
                    }
                }
            });

            // Run if files to upload
            if (filePaths.length > 0) {
                totalSize = Math.round((s / 1024 / 1024) * 100) / 100 + 'MB';
                // Start checking processed files
                let progress;
                intervalId = null;
                if (intervalId === null) {
                    intervalId = setInterval(function() {
                        $.ajax({
                            url: 'admin/countFiles.php',
                            type: 'POST',
                            data: { filePaths: filePaths },
                            success: function(response) {
                                if (response == '') { response = 0; }
                                let totalF = filePaths.length;
                                $('#notification p span').text(' ' + response + '/' + totalF);
                                
                                // Progress bar
                                if (totalF === 0) {
                                    progress = 0; // To avoid division by zero
                                }
                                progress = (response / totalF) * 100;
                                $('#taskProgress').val(progress);
                                setTimeout(function(){
                                    if (response === 0) {
                                        $('#taskProgress').val(10);
                                    }
                                }, 5000);
                            },
                            error: function(xhr, status, error) {
                                console.error("Error: ", error);
                            }
                        });
                    }, 500); // Execute every half a second
                }
                $('#notification, .notification_bg').show();
                $('#notification').html('<img class="rotating" src="images/loader.png"><p>Trwa zapisywanie danych i upload ' + totalSize + ' zdjęć<span></span><progress id="taskProgress" value="0" max="100"></progress></p>');
                $('.notification_bg').css('background-color', '#090');
            }
            
        } else {
            totalSize = 0;
        }

        // Add gusData to formData
        if (typeof gusData !== 'undefined' && gusData !== null) {
            const gusDataString = JSON.stringify(gusData);
            formData.append('gusData', gusDataString);
        }

        // Clear timer()
        //console.log('formData');
        //console.log(formData);
        //return;
        
        $.ajax({
            url: 'admin/saveRecord.php', // Adjust this URL to your PHP script for saving (insert/update)
            type: 'POST',
            contentType: false, // This is set to false since it is a multipart/form data request
            processData: false, // This is set to false to prevent jQuery from converting the data into a query string
            //dataType: 'json',
            data: formData,
            success: function(response) {
                //console.log(response);
                $('#printForm #emailConfirm').hide(); // reset
                $('#phone_info').hide();
                if (dbTable != 'orders' && response.success !== true) {
                    //
                } else if (dbTable == 'orders') {
                    if (response.sent != 1) {
                        $('#phone_info').show();
                        $('#phone').addClass('missed');
                        $('#phone_additional').addClass('missed');
                    } else {
                        if (response.confirm != '') {
                            $('#printForm #emailConfirm').show();
                            $('#printForm #emailConfirm span').text(response.confirm);
                        }
                        $('#formPopup').fadeOut(200, function(){
                            fetchRecords(dbTable, company, archived, sortByDate, id, true); // Refresh the records displayed
                            $('#notification, .notification_bg').hide();
                            // Show print box only if adding record NOT editing
                            if (!isUpdate) {
                                $('#printPopup').fadeIn();
                            } else {
                                $('#overlay').fadeOut(); // Hide the form
                            }
                            // Stop checking processed files
                            if (typeof intervalId !== 'undefined' && intervalId !== null) {
                                clearInterval(intervalId);
                                intervalId = null;
                            }
                        });
                    }
                } else {
                    $('#notification, .notification_bg').hide();
                    $('#overlay, #formPopup').fadeOut(200, function(){
                        fetchRecords(dbTable, company, archived, sortByDate); // Refresh the records displayed
                        $('#recordForm').css('visibility', 'visible');
                    }); // Hide the form
                }
            },
            error: function(xhr, status, error) {
                console.error("Error saving record: ", error);
                // Debug
                //console.log("Status: ", status);
                //console.log("Response: ", xhr.responseText); // Log the response text for debugging
            }
        });
    }
}

function cleanSpaces(str) {
    // Replace multiple spaces with single space
    str = str.replace(/\s+/g, ' ');
    // Trim the string (remove spaces before and after)
    str = str.trim();
    
    return str;
}

function popupPosition() {
    // Check the screen height and the height of the '.popup' element
    let screenHeight = window.innerHeight; // $(window).height(); - jQuery confused window with document for some reason
    let popupHeight = $('#formPopup').outerHeight(); // document.getElementById('formPopup').offsetHeight;
    let position; 
    //var topValue = $('#formPopup').css('top');
    //console.log(topValue);

    if (popupHeight > screenHeight) {
        $('#formPopup').css({
            'position': 'absolute',
            'top': '40px',
            'transform': 'translate(-50%, 0)'
        });
        if (position == 'fixed') {
            $('html, body').animate({scrollTop: 0, scrollLeft: 0}, 800, function() {
                // option to display form after scroll
            });
            position = 'absolute';
        }
    } else { // Get back to default from style.css
        $('#formPopup').css({
            'position': 'fixed',
            'top': '50%',
            'transform': 'translate(-50%, -50%)'
        });
        position = 'fixed';
    }
}

function blinkRow(id, times, speed) {
    if (times > 0) {
        $('#id_' + id).fadeOut(speed).fadeIn(speed, function() {
            blinkRow(id, times - 1, speed);
        });
    }
}

function saveCheckboxes(dbTable) {
    let checkboxValues = [];
    $('input[type="checkbox"][name="column"]').each(function() {
        checkboxValues.push(this.checked ? 1 : 0);
    });
    let dataString = checkboxValues.join(''); //join(',')

    $.ajax({
        url: "admin/saveCheckboxes.php",
        type: "POST",
        data: {checkboxState: dataString, dbTable: dbTable},
        //success: function(response) {
            //
        //},
        error: function(xhr, status, error) {
            console.error("Error saving checkbox states: ", status, error);
        }
    });
}

function playSound() {
    let audio = new Audio('sound_effect.mp3'); // Ensure you have the sound_effect.mp3 file in the correct path
    audio.play();
}

// Function to update the row count display
function updateRowCount(dbTable) {
    //let visibleRows = $('#recordsTable tbody tr:visible').length;
    let visibleRows;
    if (dbTable == 'clients') {
        visibleRows = $('#recordsTable tbody tr:visible').length + $('#recordsTable tbody tr.hideMe').length;
    } else {
        visibleRows = $('#recordsTable tbody tr:visible').length;
    }
    let archivedRows = $('#recordsTable tbody tr.archived').length; // Archived
    let ending = '';
    if (visibleRows > 1) { 
        ending = 'ów';
        if (visibleRows < 5) {
            ending = 'i';
        }
    }
    $('#rowCount').text(visibleRows + ' wynik' + ending);
    $('#archivedText').text('Archiwum (' + archivedRows + ')');
    //$('#rowCount').text(visibleRows);
}

function applySortOrder() {
    // Loop through sortOrder keys and apply sort to each column
    for (let index in sortOrder) {
        let table = $('th').eq(index).parents('table').eq(0);
        let rows = table.find('tbody tr').toArray().sort(comparer(parseInt(index)));

        if (sortOrder[index] === 'desc') {
            rows = rows.reverse();
        }

        table.find('tbody').empty().append(rows);
    }
}

function comparer(index) {
    return function(a, b) {
        let valA = getCellValue(a, index), valB = getCellValue(b, index);
        return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB);
    };
}

function getCellValue(row, index){ 
    return $(row).children('td').eq(index).text(); 
}

// New function to reapply search filters based on stored search terms in columnSearches
function applySearchFilter() {
    if (Object.keys(columnSearches).length > 0) {
        $('#recordsTable tbody tr').each(function() {
            let isMatch = true;
            let row = $(this);
            Object.keys(columnSearches).forEach(function(index) {
                let columnSearchTerm = columnSearches[index].toLowerCase();
                if (columnSearchTerm !== "") {
                    let rowText = row.find('td').eq(index).text().toLowerCase();
                    if (rowText.indexOf(columnSearchTerm) === -1) {
                        isMatch = false; // This row does not match the search criteria
                    }
                }
            });
            row.toggle(isMatch);
        });
        updateRowCount(dbTable);
    }
}