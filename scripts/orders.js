//login = $('#login_holder').text().trim();
//level = $('#level_holder').text().trim();

let remaining, timerInterval, timerInterval_monitor, refreshOnce, complaintVisibility; // hide complaints

$(document).ready(function() {

    // Tooltip
    $('body').append('<div class="tooltip"></div>'); // Create the tooltip container
    
    // Delegated event for mouseenter directly on buttons with a data-title
    $(document).on('mouseenter', '[data-title]', function() {
        console.log('show data-title');
        var titleText = $(this).attr('data-title'); // Get title text from the button
        if (!titleText) return; // If no title, do nothing
        var button = $(this); // Reference to the button
        var buttonOffset = button.offset(); // Get position of the button
        $('.tooltip')
            .html(titleText)
            .css({
                'visibility': 'hidden', // Keep it hidden while calculating width and height
                'display': 'block' // Make it block to calculate dimensions
            });
        // Calculate dimensions now that text is set
        let tooltip = document.querySelector('.tooltip');
        let maxWidth, minWidth;
        if (typeof dbTable !== 'undefined' && dbTable == 'calendar') {
            let theWidth = (document.querySelector('.cal-day').getBoundingClientRect().width - 20) + 'px';
            maxWidth = minWidth = theWidth;
        } else {
            maxWidth = '250px';
            minWidth = 'auto';
        }
        let tooltipWidth = tooltip.offsetWidth;
        let tooltipHeight = tooltip.offsetHeight;
        //var tooltipWidth = $('.tooltip').outerWidth();
        //var tooltipHeight = $('.tooltip').outerHeight();
        $('.tooltip')
            .css({
                'left': buttonOffset.left + button.outerWidth() / 2 - tooltipWidth / 2, // Center horizontally relative to the button
                'top': buttonOffset.top - tooltipHeight - 10, // Position above the button
                'visibility': 'visible', // Make it visible after positioning
                'max-width': maxWidth,
                'min-width': minWidth
            });
    });

    // Delegated event for mouseleave on the button
    $(document).on('mouseleave', '[data-title]', function() {
        $('.tooltip').hide();
    });

    $(document).on('input', '#searchBox', function() {
        var searchText = $(this).val().trim().toLowerCase();
        $('.order-box').each(function() {
            //var text = $(this).text().toLowerCase();
            var text = $(this).clone()    // Clone the element
                            .find('select') // Find select elements
                            .remove()      // Remove them
                            .end()         // Go back to the cloned element
                            .text()        // Get text content
                            .toLowerCase(); // Convert to lowercase
            var selectValue = $(this).find('select option:selected').text().toLowerCase();
            if (selectValue.includes(searchText) || text.includes(searchText)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    $(document).on('mouseenter', '.order-box', function() {
        var dataId = $(this).attr('data-id');
        if (dataId) {
            // Apply opacity to all .order-box elements with the same data-id
            $('.order-box[data-id="' + dataId + '"]').addClass('blinking'); //.css('opacity', 0.5);
        }
    });
    $(document).on('mouseleave', '.order-box', function() {
        var dataId = $(this).attr('data-id');
        if (dataId) {
            // Restore opacity to all .order-box elements with the same data-id
            $('.order-box[data-id="' + dataId + '"]').removeClass('blinking'); //.css('opacity', 1);
        }
    });

    $(document).on('change', '.order-box .urgent input[type="checkbox"], .order-box input[type="checkbox"][name="assemble"], .order-box input[type="checkbox"][name="assembleReady"]', function() {
        let select = $(this).closest('.order-box').find('select:not(.collect):not(.payment)');
        let status = select.val();
        let id = select.data('id');
        let date_quote = date_start = date_count = date_end = urgent = mark = '';
        if ($(this).parent().hasClass('urgent')) {
            mark = 'checkboxUrgent';
        } else if ($(this).attr('name') === 'assemble') {
            mark = 'checkboxAssemble';
        } else if ($(this).attr('name') === 'assembleReady') {
            mark = 'checkboxAssembleReady';
        }

        if ($(this).is(':checked')) {
            urgent = $(this).val(); // it's urgent or assemble
        }
        //console.log(urgent);
        changeStatus(id, status, date_quote, date_start, date_count, date_end, urgent, mark, '');
    });

    $(document).on('change', '.order-box .collect, .order-box .payment', function() {
        let select = $(this);
        let method = select.val();
        let id = select.data('id');
        let type;
        if (select.hasClass('collect')) {
            type = 'collect';
        } else if (select.hasClass('payment')) {
            type = 'payment';
        }
        changeCollect(id, method, type);
    });

    $(document).on('click change', '.addNote, #flexContainer select:not(.collect):not(.payment), td[data-column="status"] select', function(e) {
        let select = $(this);
        let status = select.val();
        let id = select.data('id');
        let mark = '';
        let tag;
        let date_quote, date_start, date_count, date_end, urgent, formContent;
        
        if ((($(this).hasClass('addNote') && e.type === 'click') || (e.type === 'change' && !$(this).hasClass('addNote'))) &&
            $(this).closest('#flexContainer').length > 0) {
            // BOX
            tag = 'box';

            clonedBox = $('#boxId-' + id).clone();
            processClonedBox();
        } else if ((($(this).hasClass('addNote') && e.type === 'click') || (e.type === 'change' && !$(this).hasClass('addNote'))) &&
            $(this).closest('td[data-column="status"]').length > 0) {
            // TABLE
            tag = 'table';
    
            fetchOrdersSimple(id, function(result, error) {
                if (error) {
                    console.error("Error: ", error);
                } else {
                    clonedBox = $(result);
                    processClonedBox();
                }
            });
        }
    
        function processClonedBox() {
            date_quote = date_start = date_count = date_end = urgent = '';
    
            if (status != '' && status != 'Zlecona weryfikacja' && status != 'Zlecona naprawa' && status != 'Wydać bez naprawy') {
                changeStatus(id, status, date_quote, date_start, date_count, date_end, urgent, mark, tag);
            } else {
                $('html, body').animate({
                    scrollTop: 0,
                    scrollLeft: 0
                }, 800);
    
                if ($(this).is(':checked')) {
                    urgent = $(this).parent().find('.urgent input[type="checkbox"]');
                }
                let columnName;
                if (status == 'Zlecona weryfikacja') {
                    columnName = 'verification';
                    whatIs = 'weryf.';
                } else if (status == 'Zlecona naprawa') {
                    columnName = 'repair';
                    whatIs = 'naprawy';
                } else if (status == 'Wydać bez naprawy') {
                    columnName = 'repair';
                    whatIs = 'naprawy';
                } else {
                    columnName = 'interference_note';
                    whatIs = '';
                }

                let getDateStart = clonedBox.find('.' + columnName + 'DateStart').val();
                let getDateCount = clonedBox.find('.' + columnName + 'DateCount').val();
                let getDate = clonedBox.find('.' + columnName + 'Date').val();
                let getQuote = clonedBox.find('.' + columnName + 'Quote').val();
                let getInterferenceNote = clonedBox.find('.interferenceNote').val();
                
                clonedBox.attr('id', 'boxId');
                clonedBox.find('select').remove();
                clonedBox.find('.hideTr').remove();
                clonedBox.find('.addNote').remove();
                clonedBox.find('.urgent').remove();
                clonedBox.find('.backToList').removeClass('backToList'); // only informational
                if (clonedBox.hasClass('status-2') || clonedBox.hasClass('status-7') || clonedBox.hasClass('status-10')) {
                    clonedBox.addClass('addBorder');
                }
                if (getDateStart == '' ) {
                    getDateStart = new Date().toISOString().split('T')[0];
                }
                let boxContent = clonedBox.prop('outerHTML');
                formContent = '';

                if (whatIs == '') {
                    
                    formContent += '<label for="' + columnName + '">Notatka do podz.</label>';
                    formContent += '<textarea placeholder="Notatka do podzespołu" id="' + columnName + '" name="' + columnName + '">' + getInterferenceNote + '</textarea>';
        
                    formContent += '<button style="margin-top:15px" type="button" id="saveBtn_order_note">Potwierdź</button>';
                    formContent += '<button style="margin-top:15px" type="button" class="cancelBtn">Anuluj</button>';

                } else {
                    if (level === 'admin') {
                        formContent += '<label for="' + columnName + '_quote">Wycena ' + whatIs + '</label>';
                        formContent += '<input placeholder="Wycena" type="text" id="' + columnName + '_quote" name="' + columnName + '_quote" value="' + getQuote + '"><br>';
                    }
    
                    formContent += '<label for="' + columnName + '_date_start">Data zlecenia</label>';
                    formContent += '<input placeholder="Data zlecenia" type="date" id="' + columnName + '_date_start" name="' + columnName + '_date_start" value="' + getDateStart + '"><br>';
                    formContent += '<label for="' + columnName + '_date_count">Dni robocze</label>';
                    formContent += '<input placeholder="Dni robocze" type="text" id="' + columnName + '_date_count" name="' + columnName + '_date_count" value="' + getDateCount + '"><br>';
                    formContent += '<label for="' + columnName + '_date">Termin</label>';
                    formContent += '<input placeholder="Termin" type="date" id="' + columnName + '_date" name="' + columnName + '_date" value="' + getDate + '"><br>';
        
                    formContent += '<div class="checkboxContainer confirmDeadline" style="display:none; color:red">';
                    formContent += '<label for="deadline_confirmation">';
                    formContent += '<div style="color:red">';
                    formContent += '<strong>UWAGA!</strong> Już <span>jest (1) termin</span> na ten dzień - potwierdź datę';
                    formContent += '</div>';
                    formContent += '</label>';
                    formContent += '<input type="checkbox" id="deadline_confirmation" class="deadline_confirmation_checkbox" name="deadline_confirmation" value="TAK"><br>';
                    formContent += '</div>';
        
                    formContent += '<input style="display:none" type="checkbox" id="' + columnName + '_accepted" class="' + columnName + '_accepted_checkbox" name="' + columnName + '_accepted" value="TAK" checked>';
        
                    formContent += '<button style="margin-top:15px" type="button" id="saveBtn_order">Potwierdź</button>';
                    formContent += '<button style="margin-top:15px" type="button" class="cancelBtn">Anuluj</button>';
                }
                
                $('#recordForm').html(boxContent + formContent);
                
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

                $('#overlay, #formPopup').fadeIn();
                //$(document).on('click', '#saveBtn_order', function() { // triggers changeStatus +1 every time

                $('#saveBtn_order_note').on('click', function() {
                    let interferenceNote = $('#interference_note').val();
                    changeStatus(id, interferenceNote, '', '', '', '', '', 'note', tag);
                });

                $('#saveBtn_order').on('click', function() {
                    let letsProceed = true;
                    let date_quote = $('#' + columnName + '_quote').val();
                    let date_start = $('#' + columnName + '_date_start').val();
                    let date_count = $('#' + columnName + '_date_count').val();
                    let date_end = $('#' + columnName + '_date').val();
                    if ($('#' + columnName + '_quote').val() === '') {
                        $('#' + columnName + '_quote').addClass('missed');
                        letsProceed = false;
                    }
                    if ($('#' + columnName + '_date_start').val() === '') {
                        $('#' + columnName + '_date_start').addClass('missed');
                        letsProceed = false;
                    }
                    if ($('#' + columnName + '_date_count').val() === '') {
                        $('#' + columnName + '_date_count').addClass('missed');
                        letsProceed = false;
                    }
                    if ($('#' + columnName + '_date').val() === '') {
                        $('#' + columnName + '_date').addClass('missed');
                        letsProceed = false;
                    }
                    if (letsProceed) {
                        changeStatus(id, status, date_quote, date_start, date_count, date_end, urgent, mark, tag);
                    }
                });
            }
        }
    });

    $('#temp').hide();
    $(document).on('click', '.resizePanel', function() {
        $('.header').hide();
        $('#dateTime').hide();
        $('#recordsTable').hide();
        $('html, body').css('padding', '0');
        $('body').css('padding-bottom', '20px');
        $('#temp').show();
    });
    $(document).on('click', '#resizePanel2', function() {
        $('.header').show();
        $('#dateTime').show();
        $('#recordsTable').show();
        $('html, body').css('padding', '20px');
        $('body').css('padding-bottom', '0');
        $('#temp').hide();
    });

    $(document).on('keyup', '#verification_date_count', function() {
        dateCount('verification', 'date');
    });
    $(document).on('change', '#verification_date_start', function() {
        dateCount('verification', 'date');
    });
    $(document).on('keyup', '#repair_date_count', function() {
        dateCount('repair', 'date');
    });
    $(document).on('change', '#repair_date_start', function() {
        dateCount('repair', 'date');
    });
    $(document).on('change', '#verification_date', function() {
        dateCount('verification', 'days');
    });
    $(document).on('change', '#repair_date', function() {
        dateCount('repair', 'days');
    });

    // hide complaints
    $(document).on('click', '#hideComplaints', function() {
        updateDatabase('others', 'complaints', '', 'select', 'switch');
    });
    // hide complaints
    // keep monitoring if to show or hide complaints
    if (!timerInterval_monitor) {
        timerInterval_monitor = setInterval(function() {
            updateDatabase('others', 'complaints', '', 'select', 'check', dbTable);
        }, 1000);
    }
});

// hide complaints
function updateDatabase(dbTable, name, value, method, xswitch, current_dbTable = '') {
    $.ajax({
        url: 'admin/updateDatabase.php',
        type: 'POST',
        data: { dbTable: dbTable, name: name, value: value, method: method },
        dataType: 'json',
        success: function(response) {
            if (xswitch === 'switch') {
                if (response.success != '') {
                    updateDatabase('others', 'complaints', null, 'update', '');
                    complaintVisibility = 1;
                } else {
                    updateDatabase('others', 'complaints', currentDateTime(), 'update', '');
                    complaintVisibility = currentDateTime();
                }
                fetchOrders();
            } else if (xswitch === 'check') {
                if (response.success != '') {
                    complaintVisibility = response.success;
                } else {
                    complaintVisibility = 1;
                }
                fetchOrders('auto');

                // Refresh the table
                if (typeof totalrows === 'undefined') {
                    totalrows = 0;
                }
                //console.log(current_dbTable, totalrows, response.totalrows);
                if (current_dbTable == 'orders' && totalrows !== response.totalrows) {
                    if (totalrows > 0) {
                        fetchRecords(current_dbTable, company, archived, sortByDate);
                    }
                    totalrows = response.totalrows;
                }
            }
        },
        error: function(xhr, status, error) {
            console.error("Error fetching records: ", error);
        }
    });
}

function dateCount(field, method) {
    if ($('#' + field + '_date_start').val() && ($('#' + field + '_date_count').val() || $('#' + field + '_date').val())) {
        $.ajax({
            type: "POST",
            url: "admin/workingDays.php",
            data: {
                startDate: $('#' + field + '_date_start').val(), 
                daysToAdd: $('#' + field + '_date_count').val(), 
                endDate: $('#' + field + '_date').val(),
                method: method,
                column: field
            },
            success: function(response) {
                console.log(response);
                let dl = response.deadlines;
                let msg;
                //let upperField = field.charAt(0).toUpperCase() + field.slice(1);

                if (dl > 0) {
                    if (dl == 1) { msg = 'jest (' + dl + ') termin'; }
                    else if (dl < 5) { msg = 'są (' + dl + ') terminy'; }
                    else if (dl >= 5) { msg = 'jest (' + dl + ') terminów'; }
                    $('#' + field + '_date').addClass('highlight_red');
                    $('#' + field + '_date').parent().find('.confirmDeadline').show().find('span').text(msg);
                } else {
                    // Reset
                    $('#' + field + '_date').removeClass('highlight_red');
                    $('#' + field + '_date').parent().find('.confirmDeadline').hide();
                    //$('#' + field + '_date').next('.confirmDeadline').hide();
                }

                if (method == 'date') {
                    $('#' + field + '_date').val(response.date).removeClass('missed');
                } else {
                    $('#' + field + '_date_count').val(response.date).removeClass('missed');
                }
            }
        });
    } else {
        $('#' + field + '_date').val('');
    }
}

function changeCollect(id, method, type) {
    $.ajax({
        url: 'orders/updateCollect.php',
        type: 'POST',
        cache: false,
        data: { id: id, method: method, type: type },
        dataType: 'json',
        success: function(response) {
            
            $('html, body').animate({
                scrollTop: $('#boxId-' + id).offset().top,
                scrollLeft: 0
            }, 800, function() {
                //setTimeout(function() {
                    blink(id, 2, 250);
                //}, 800);
            });

        },
        error: function(xhr, status, error) {
            console.error("Error fetching records: ", error);
        }
    });
    return;
}

function changeStatus(id, status, date_quote, date_start, date_count, date_end, urgent, mark, tag) {
    // Can't use both # for a single fadeOut as it triggers AJAX twice
    $('#overlay, #formPopup').fadeOut(250);
    /*
    alert(
        'id:' + id + 
        '\nstatus:' + status +
        '\ndate_quote:' + date_quote +
        '\ndate_start:' + date_start +
        '\ndate_count:' + date_count +
        '\ndate_end:' + date_end +
        '\nurgent:' + urgent +
        '\nmark:' + mark +
        '\ntag:' + tag
    );
    */
    //$('#overlay').fadeOut(250, function(){
        $.ajax({
            url: 'orders/updateOrder.php',
            type: 'POST',
            cache: false,
            data: { id: id, status: status, date_quote: date_quote, date_start: date_start, date_count: date_count, date_end: date_end, urgent: urgent, mark: mark },
            dataType: 'json',
            success: function(response) {
                console.log(response);
                if (response.success) {
                    if (tag == 'table') {
                        //let company = $('#company').val();
                        fetchRecords(dbTable, company, archived, sortByDate, id, true); // Refresh the records displayed
                    } else {
                        fetchOrders('', id);
                    }
                }
                
            },
            error: function(xhr, status, error) {
                console.error("Error fetching records: ", error);
            }
        });
        return;
    //});
}

function blink(id, times, speed) {
    if (times > 0) {
        $('#boxId-' + id).fadeOut(speed).fadeIn(speed, function() {
            blink(id, times - 1, speed);
        });
    }
}

let orderOptions = [
    'Przyjęto na magazyn',
    'Zlecona weryfikacja',
    'Rozebrane do wyceny', 
    'Wydać bez naprawy',
    'Wyceniona naprawa',
    'Zlecona naprawa',
    'Zrobić test',
    'Wydać po naprawie',
    'Zakończono',
    'Reklamacja'
];

function fetchOrdersSimple(id = '', callback) {
    $.ajax({
        url: 'orders/fetchOrders.php',
        type: 'POST',
        dataType: 'json',
        success: function(orders) {
            console.log(orders);
            let jsonData = JSON.stringify(orders);
            let parsedData = JSON.parse(jsonData);

            let htmlString = parsedData.receive.join("") +
                             parsedData.verify.join("") +
                             parsedData.deliver.join("") +
                             parsedData.appraisal.join("") +
                             parsedData.forappraisal.join("") +
                             parsedData.repair.join("") +
                             parsedData.test.join("") +
                             parsedData.after.join("") +
                             parsedData.complaint.join("") +
                             parsedData.finish.join("") +
                             parsedData.undefined.join("");

            let $html = $(htmlString);
            let $targetDiv = $html.filter("#boxId-" + id);
            let targetDivHtml = $targetDiv.prop('outerHTML');
            
            if (callback) {
                callback(targetDivHtml);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error fetching records: ", error);
            if (callback) {
                callback(null, error);
            }
        }
    });
}

function fetchOrders(method = '', id = '') {
    $('#loading').show();

    $.ajax({
        url: 'orders/fetchOrders.php',
        type: 'POST',
        dataType: 'json',
        success: function(orders) {

            login = orders.login; // GLOBAL
            level = orders.level; // GLOBAL - for future use

            if (complaintVisibility !== 1) {
                remaining = getRemainingTime(complaintVisibility); // hide complaints
            }

            // Update only when something changed
            if ((method == '') || (method == 'auto' && (typeof update === 'undefined' || orders.update != update))) {
                update = orders.update;
                
                let counted = orders.repair.length + orders.verify.length + orders.deliver.length + orders.receive.length + orders.appraisal.length + orders.forappraisal.length + orders.after.length + orders.complaint.length;
                let header1 = header2 = header3 = header4 = header5 = header6 = header7 = header8 = header10 = '';

                let ending = '';
                if (counted > 1) { 
                    ending = 'ów';
                    if (counted < 5) {
                        ending = 'i';
                    }
                }
                $('#rowCount').text(counted + ' wynik' + ending);

                //if (dbTable == 'orders') {
                    let of = orders.forgotten;
                    let of2 = orders.forgotten2;
                    let of3 = orders.forgotten3;
                    let fourteen, twentyone;
                    if (of2 > 0) {
                        fourteen = ' &bull; <strong>(' + of2 + ')</strong> od 14+ dni';
                        of = of - of2 - of3;
                    }
                    if (of3 > 0) {
                        twentyone = ' &bull; <strong>(' + of3 + ')</strong> od 21+ dni';
                        of2 = of2 - of3;
                    }
                    let ofz1 = ofz2 = '';
                    if (of < 0) {
                        of = 0;
                    }
                    if (of > 0 || of2 > 0 || of3 > 0) {
                        if (of == 1) {
                            ofz1 = 'zlecenie';
                            ofz2 = 'o';
                        } else if (of < 5 && of > 1) {
                            ofz1 = 'zlecenia';
                            ofz2 = 'y';
                        } else {
                            ofz1 = 'zleceń';
                            ofz2 = 'o';
                        }
                        let fImg = '<img src=\"images/warning2.png\">';
                        //$('#infoBar').html(fImg + '<strong>UWAGA:</strong> (' + of + ') ' + ofz1 + ' nie zmienił' + ofz2 + ' statusu od co najmniej 7 dni' + fourteen + fImg).show();
                        if (typeof dbTable === 'undefined' || dbTable == 'panel') {
                            $('#infoBar').html(fImg + '<strong>UWAGA:</strong> <strong>(' + of + ')</strong> ' + ofz1 + ' bez zmian od 7+ dni' + fourteen + twentyone + fImg).show();
                        }
                        $('body').css('padding-bottom', '31px');
                    } else {
                        $('#infoBar').hide(); 
                        $('body').css('padding-bottom', '0');
                    }
                //}

                // hide complaints
                if (complaintVisibility === 1 || (complaintVisibility !== 1 && remaining.startsWith("-"))) {
                    complaints = orders.complaint.join('');
                } else {
                    complaints = '';
                }

                // Column #1
                let complaintCount = orders.complaint.length;
                /*if (complaintCount > 0) {
                    header1 = '<div class="order-header status-1">' + complaintCount + '&nbsp;reklamacje</div>';
                    header1 = orders.complaint.join('');
                }*/
                let repairCount = orders.repair.length;
                if (repairCount > 0) {
                    header2 = '<div class="order-header status-2">' + repairCount + '&nbsp;zlecone naprawy</div>';
                    header2 += complaints + orders.repair.join('');
                }

                // Column #2
                let verifyCount = orders.verify.length;
                if (verifyCount > 0) {
                    header3 = '<div class="order-header status-3">' + verifyCount + '&nbsp;zlecone weryfikacje</div>';
                    header3 += orders.verify.join('');
                }
                let testCount = orders.test.length;
                if (testCount > 0) {
                    header4 = '<div class="order-header status-4">' + testCount + '&nbsp;zrobić test</div>';
                    header4 += orders.test.join('');
                }

                // Column #3
                let deliverCount = orders.deliver.length;
                if (deliverCount > 0) {
                    header5 = '<div class="order-header status-5">' + deliverCount + '&nbsp;do wydania bez naprawy</div>';
                    header5 += orders.deliver.join('');
                }
                let afterCount = orders.after.length;
                if (afterCount > 0) {
                    header6 = '<div class="order-header status-6">' + afterCount + '&nbsp;wydać po naprawie</div>';
                    header6 += orders.after.join('');
                }

                // Column #4
                let receiveCount = orders.receive.length;
                if (receiveCount > 0) {
                    header7 = '<div class="order-header status-7">' + receiveCount + '&nbsp;przyjęto na magazyn</div>';
                    header7 += orders.receive.join('');
                }
                let appraisalCount = orders.appraisal.length;
                if (appraisalCount > 0) {
                    header8 = '<div class="order-header status-8">' + appraisalCount + '&nbsp;wyceniona naprawa</div>';
                    header8 += orders.appraisal.join('');
                }
                let forappraisalCount = orders.forappraisal.length;
                if (forappraisalCount > 0) {
                    header10 = '<div class="order-header status-10">' + forappraisalCount + '&nbsp;rozebrane do wyceny</div>';
                    header10 += orders.forappraisal.join('');
                }

                if (complaintCount + repairCount == 0) {
                    $('#ordersContainer1').hide();
                } else {
                    $('#ordersContainer1').show().empty().append(header1 + header2);
                }

                if (verifyCount + testCount == 0) {
                    $('#ordersContainer2').hide();
                } else {
                    $('#ordersContainer2').show().empty().append(header3 + header4);
                }

                if (deliverCount + afterCount == 0) {
                    $('#ordersContainer3').hide();
                } else {
                    $('#ordersContainer3').show().empty().append(header5 + header6);
                }

                if (receiveCount + appraisalCount + forappraisalCount == 0) {
                    $('#ordersContainer4').hide();
                } else {
                    $('#ordersContainer4').show().empty().append(header7 + header8 + header10);
                }
                
                if ($('#temp2').is(':visible')) {
                    $('div[class="order-header status-2"]').css('text-align', 'right');
                    $('div[class="order-header status-7"]').css('text-align', 'left');
                    $('#temp').show();
                }

                /*if (level == 'admin' || level == 'editor') {
                    if ($('#ordersContainer4').length === 0) {
                        $('#flexContainer').append('<div class="orderContainers" id="ordersContainer4"></div>');
                    }
                    $('#ordersContainer4').show().empty().append(header7 + header8);
                }*/

                if (id != '') {
                    $('html, body').animate({
                        scrollTop: $('#boxId-' + id).offset().top,
                        scrollLeft: 0
                    }, 800, function() {
                        //setTimeout(function() {
                            blink(id, 2, 250);
                        //}, 800);
                    });
                }
                adjustContainerWidths();
            }

            // hide complaints
            complaintsReset();
            if (!timerInterval) {
                timerInterval = setInterval(function() {
                    complaintsReset();
                }, 1000);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error fetching records: ", error);
        }
    });
    
    $('#loading').hide();
}

// hide complaints
function complaintsReset() {
    if (complaintVisibility === 1 || (complaintVisibility !== 1 && remaining.startsWith("-"))) {
        clearInterval(timerInterval);
        updateDatabase('others', 'complaints', null, 'update', '');
        $('#hideComplaintsTimer').text('');
        // refresh once
        if (refreshOnce !== true) {
            fetchOrders();
            refreshOnce = true;
        }
        refreshOnce_off = false;
    } else {
        $('#hideComplaintsTimer').text(`Reklamacje ${remaining}`);
        // refresh once
        if (refreshOnce_off !== true) {
            fetchOrders();
            refreshOnce_off = true;
        }
        refreshOnce = false;
    }
}

// hide complaints
function getRemainingTime(futureDateTime) {
    let futureDate = new Date(futureDateTime);
    futureDate.setTime(futureDate.getTime() + 12 * 60 * 60 * 1000); // 12 * 60 * 60 * 1000 vs. 60 * 1000
    let now = new Date();
    let diff = futureDate - now;

    // Calculate the difference in hours, minutes, and seconds
    let hours = Math.floor(diff / (1000 * 60 * 60));
    let minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    let seconds = Math.floor((diff % (1000 * 60)) / 1000);

    // Format the hours, minutes, and seconds to always have 2 digits
    hours = String(hours).padStart(2, '0');
    minutes = String(minutes).padStart(2, '0');
    seconds = String(seconds).padStart(2, '0');

    return `${hours}:${minutes}:${seconds}`;
}

function adjustContainerWidths() {
    var $visibleContainers = $('.orderContainers:visible');
    //if (window.innerWidth > 800) {
        var numVisible = $visibleContainers.length;
        var newWidth = (100 / numVisible) - 3; // Subtract margin percentage
    
        $visibleContainers.css('flex', `1 0 ${newWidth}%`);
    //}
}