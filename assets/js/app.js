import '../styles/style.scss';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('service-worker.js', {
            scope: '.'
        }).then(function(registration) {
            // Registration was successful
            console.log('Registered!');
        }, function(err) {
            // registration failed :(
            console.log('ServiceWorker registration failed: ', err);
        }).catch(function(err) {
            console.log(err);
        });
    });
} else {
    console.log('service worker is not supported');
}

let currentQuestion = 1;
let correctAnswers = 0;


const btnAdd = document.getElementById("installApp")

if (btnAdd) {
    let storedInstallEvent = null // Store to keep triggerable event after a first dismiss
    window.addEventListener('beforeinstallprompt', e => {
        e.preventDefault()
        storedInstallEvent = e
        btnAdd.addEventListener('click', function () {
            storedInstallEvent.prompt()
        })
    })
}

$(document).ready(function(){

    if (window.location.href.indexOf("download") > -1) {
        //getQuestion();
        console.log('get question');
        var intervalId = window.setInterval(function(){
            //getQuestion();
            console.log('get question');
        }, 61100);
    }

    var isRoot = location.pathname == "/";

    let maxQuestions = 10;

    if(isRoot){
        maxQuestions = 3
    }

    if($('.quiz-game').length){
        $('.question-wrapper').hide();
        $('.question-wrapper[data-question="1"]').show();
        $('.summary').hide();
    }

    $('.quiz-choice').click(function() {
        $('.quiz-choice').removeClass('selected');
        $(this).addClass('selected');
        $('#submitQuiz').prop("disabled", false);
    });

    $('#submitQuiz').click(function() {
        if($(this).hasClass('next-question')){

            $(this).removeClass('next-question').text('Valider').prop("disabled", true);
            $('.question-wrapper[data-question="' + currentQuestion + '"]').remove();
            currentQuestion = currentQuestion + 1;

            if(currentQuestion <= maxQuestions){
                let category = $('.question-wrapper[data-question="' + currentQuestion + '"]').data('difficulty');
                $('.random-difficulty').text(category);
                $('.question-wrapper[data-question="' + currentQuestion + '"]').show();
            }else {

                $('#submitQuiz').hide();
                $('.summary').show();
            }
        }else{

            let questionOid = $('.question-wrapper[data-question="' + currentQuestion + '"]').data('qoid');
            let questionId = $('.question-wrapper[data-question="' + currentQuestion + '"]').data('qid');
            let answer = $('.quiz-choice.selected').data('value');
            getCorrectAnswer(questionOid, questionId, answer);
        }
    });

    // leader board collapse

    $( ".collapsable .leader" ).each(function() {
        if($( this ).parent().hasClass('collapsable--stats') && $( this ).index() > 3){
            $( this ).hide();
        }else if(!$( this ).parent().hasClass('collapsable--stats') && $( this ).index() > 2){
            $( this ).hide();
        }
    });

    $( ".collapser" ).click(function() {

        $(this).toggleClass('expanded');

        $( this ).closest('.leaderboard').find('.leader').each(function() {
            if($( this ).parent().hasClass('collapsable--stats') && $( this ).index() > 3){
                if( $(this).is(':visible')){
                    $( this ).removeClass('open');
                }
                $(this).slideToggle( 300, function() {
                    $(this).toggleClass('open', $(this).is(':visible'));
                });
            }else if(!$( this ).parent().hasClass('collapsable--stats') && $( this ).index() > 2){
                if( $(this).is(':visible')){
                    $( this ).removeClass('open');
                }
                $(this).slideToggle( 300, function() {
                    $(this).toggleClass('open', $(this).is(':visible'));
                });
            }
        });
    });

    // remove amchart logo on pie chart
    removeAmchartLogo();
    $( "#favoriteCategories" ).click(function() {
        removeAmchartLogo();
    });

});

function removeAmchartLogo(){
    $( "g" ).each(function( index ) {
        if($(this).attr("aria-labelledby") === "id-66-title"){
            $(this).remove();
        }
    });
}

function getQuestion() {

    $.ajax({
        url:        'https://www.openquizzdb.org/api.php?key=' + secret.apiKey,
        type:       'POST',
        dataType:   'json',
        async:      true,

        success: function(data, status) {
            console.log(data);
            if(data.response_code === 0){
                saveQuestion(data);
            }else {
                console.log('to many requests');
                blocked = blocked + 1;
                $('.blocked').text(blocked);
            }
        },
        error : function(xhr, textStatus, errorThrown) {
            console.log('failed to get question');
        }
    });

}

let duplicates = 0;
let added = 0;
let blocked = 0;

function saveQuestion(data) {

    let question = data;

    $.ajax({
        url:        '/savequestion/ajax?data=' + JSON.stringify(data),
        type:       'POST',
        dataType:   'json',
        async:      true,

        success: function(data, status) {

            $('.categories-list').empty();
            for (const [key, value] of Object.entries(data.categories)) {
                $('.categories-list').append('<li>' + key + ' : <b>' + value + '</b></li>');
            }

            if(data.duplicate){
                duplicates = duplicates + 1;
            }else{
                added = added + 1;

                let today = new Date();
                let date = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();
                let time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
                let dateTime = date +' '+ time;
                $('.questions').append('<div>' + dateTime +'</div>');
                $('.questions').append('<li>' + question.results[0].question +'<div><b>'+ question.results[0].reponse_correcte + '</b></div></li>');
                $('.counter').text(data.count);
            }

            $('.new').text(added);
            $('.duplicate').text(duplicates);
            $('.success').text(((added / (added + duplicates)) * 100).toFixed(2) + '%');

        },
        error : function(xhr, textStatus, errorThrown) {
            console.log('failed to compare question in db');
        }
    });

}

function getCorrectAnswer(questionOid, questionId, answer) {

    $.ajax({
        url:        '/getcorrectanswers/ajax?data=' + JSON.stringify(questionOid),
        type:       'POST',
        dataType:   'json',
        async:      true,

        success: function(data, status) {
            let correctAnswer = data.answer;
            manageResult(correctAnswer, questionId, answer);
        },
        error : function(xhr, textStatus, errorThrown) {
            console.log('failed to get question correct answer');
        }
    });
}

function saveAnswer(questionId, category, score) {

    let data = {
        questionId : questionId,
        category : category,
        score : score
    };

    $.ajax({
        url:        '/saveanswer/ajax?data=' + JSON.stringify(data),
        type:       'POST',
        dataType:   'json',
        async:      true,

        success: function(data, status) {
            updateAverage(category)
        },
        error : function(xhr, textStatus, errorThrown) {
            console.log('failed to save answer to database');
        }
    });
}

function updateAverage(category) {

    let data = {
        category : category
    };

    $.ajax({
        url:        '/getaverage?data=' + JSON.stringify(data),
        type:       'POST',
        dataType:   'json',
        async:      true,

        success: function(data, status) {
            let average = data.average;
            $('.average-score').text(average + '/10');
        },
        error : function(xhr, textStatus, errorThrown) {
            console.log('failed to save update average');
        }
    });
}

function manageResult(correctAnswer, questionId, answer){
    let difficulty = $('.question-wrapper[data-question="' + currentQuestion + '"]').data('difficulty');
    let category = $('.question-wrapper[data-question="' + currentQuestion + '"]').data('category');
    let score = 0;

    if(answer.toString() === correctAnswer.toString()){
        $('.quiz-choice.selected').addClass('correct');
        correctAnswers = correctAnswers + 1;
        $('.summary .score .result').text(correctAnswers);
        score = getScore(difficulty);
    }else{
        $('.quiz-choice.selected').addClass('wrong');
        $('.question-wrapper[data-question="' + currentQuestion + '"] .quiz-choice[data-value="' + correctAnswer + '"]').addClass('correct');
    }

    saveAnswer(questionId, category, score)

    $('#submitQuiz').addClass('next-question').text('Continuer');
}

function getScore(difficulty){
    let score = 0;

    if(difficulty === 'débutant'){
        score = 1;
    }else if(difficulty === 'confirmé'){
        score = 2;
    }else if(difficulty === 'expert'){
        score = 3;
    }

    return score;
}