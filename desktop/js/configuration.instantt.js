
$('body').off('click', '#reinitAllCmds').on('click', '#reinitAllCmds', function () {



        alert('toto');


        $.post({
            url: "plugins/instantt/core/ajax/instantt.ajax.php",
            data: {
                action: 'reinitAllCmds'
            },
            cache: false,
            dataType: 'json',
            success: function (data) {
                alert('fdsfds');

                // if (data.state != 'ok') {
                //     $('.actions-detail').showAlert({
                //         message: data.result,
                //         level: 'danger'
                //     });
                // }
                // else {
                //     $('.actions-detail').showAlert({
                //         message: data.result.eqLogic + ' équipements ont été réinitialisés',
                //         level: 'success'
                //     });
                // }
            }
        });


})


