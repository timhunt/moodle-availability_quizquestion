YUI.add('moodle-availability_quizquestion-form', function (Y, NAME) {

M.availability_quizquestion = M.availability_quizquestion || {};

M.availability_quizquestion.form = Y.Object(M.core_availability.plugin);

/**
 * Quizzes available for selection (alphabetical order).
 *
 * @property quizzes
 * @type Array
 */
M.availability_quizquestion.form.quizzes = null;

/**
 * States available for selection.
 *
 * @property states
 * @type Array
 */
M.availability_quizquestion.form.states = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} quizzes Array of objects containing quiz .id and .name
 * @param {Array} states Array of objects containing state .shortname and .displayname
 */
M.availability_quizquestion.form.initInner = function(quizzes, states) {
    this.quizzes = quizzes;
    this.states = states;
};

M.availability_quizquestion.form.getNode = function(json) {
    var i;

    // Create HTML structure.
    var html = '<span class="availability-group">';
    html += '<label><span class="p-r-1">' + M.util.get_string('title', 'availability_quizquestion') + '</span> ' +
            '<select name="quizid" class="custom-select">' +
            '<option value="">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    for (i = 0; i < this.quizzes.length; i++) {
        // String has already been escaped using format_string.
        html += '<option value="' + this.quizzes[i].id + '">' + this.quizzes[i].name + '</option>';
    }
    html += '</select></label>';

    html += ' <label><span class="sr-only">' + M.util.get_string('label_question', 'availability_quizquestion') + '</span>' +
            '<select name="questionid" class="custom-select">' +
            '<option value="">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    html += '</select></label>';

    html += ' <label><span class="sr-only">' + M.util.get_string('label_state', 'availability_quizquestion') + '</span>' +
            '<select name="requiredstate" class="custom-select">' +
            '<option value="">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    for (i = 0; i < this.states.length; i++) {
        html += '<option value="' + this.states[i].shortname + '">' + this.states[i].displayname + '</option>';
    }
    html += '</select></label>';

    html += '</span>';

    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

    var updateQuestions = function(quizNode, questionNode, callback) {
        var quizId = quizNode.get('value');
        var url = M.cfg.wwwroot + '/availability/condition/quizquestion/ajax.php?quizid=' + quizId;
        // First, remove all options except the first one from the question drop-down menu.
        questionNode.all('option').each(function(optionNode) {
            if (optionNode.get('value') !== '') {
                optionNode.remove();
            }
        }, this);

        if (quizId) {
            // Disable the quiz element until we finish loading it's questions.
            quizNode.set('disabled', true);
            var pendingKey = {};
            M.util.js_pending(pendingKey);
            Y.io(url, {
                on: {
                    success: function(id, response) {
                        var questions = Y.JSON.parse(response.responseText);
                        for (var i = 0; i < questions.length; i++) {
                            var questionOption = document.createElement('option');
                            questionOption.value = questions[i].id;
                            questionOption.innerHTML = questions[i].name;
                            questionNode.append(questionOption);
                        }
                        // Questions are loaded, so we enable the quiz element now.
                        quizNode.set('disabled', false);

                        if (callback !== undefined) {
                            callback();
                        }

                        M.core_availability.form.update();
                        M.util.js_complete(pendingKey);
                    },
                    failure: function(id, response) {
                        // Loading failed. Let's enable the quiz so the user can try again.
                        quizNode.set('disabled', false);
                        M.util.js_complete(pendingKey);

                        var debugInfo = response.statusText;
                        if (M.cfg.developerdebug) {
                            debugInfo += ' (' + url + ')';
                        }
                        new M.core.exception({message: debugInfo});
                    }
                }
            });
        }
    };

    // Set initial value if specified.
    if (json.quizid !== undefined &&
            node.one('select[name=quizid] > option[value=' + json.quizid + ']')) {
        node.one('select[name=quizid]').set('value', '' + json.quizid);
        updateQuestions(node.one('select[name=quizid]'), node.one('select[name=questionid]'), function() {
            if (json.questionid !== undefined &&
                node.one('select[name=questionid] > option[value=' + json.questionid + ']')) {
                node.one('select[name=questionid]').set('value', '' + json.questionid);
            }
        });
    }
    if (json.requiredstate !== undefined &&
            node.one('select[name=requiredstate] > option[value=' + json.requiredstate + ']')) {
        node.one('select[name=requiredstate]').set('value', '' + json.requiredstate);
    }

    // Add event handlers (first time only).
    if (!M.availability_quizquestion.form.addedEvents) {
        M.availability_quizquestion.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_quizquestion select');
        root.delegate('change', function() {
            var ancestorNode = this.ancestor('span.availability_quizquestion');
            var quizNode = ancestorNode.one('select[name=quizid]');
            var questionNode = ancestorNode.one('select[name=questionid]');
            updateQuestions(quizNode, questionNode);
        }, '.availability_quizquestion select[name=quizid]');
    }

    return node;
};

M.availability_quizquestion.form.fillValue = function(value, node) {
    var quizid = node.one('select[name=quizid]').get('value');
    var questionid = node.one('select[name=questionid]').get('value');
    var state = node.one('select[name=requiredstate]').get('value');

    value.quizid = quizid === '' ? '' : parseInt(quizid, 10);
    value.questionid = questionid === '' ? '' : parseInt(questionid, 10);
    value.requiredstate = state;
};

M.availability_quizquestion.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    if (value.quizid === '') {
        errors.push('availability_quizquestion:error_selectquiz');
    }
    if (value.questionid === '') {
        errors.push('availability_quizquestion:error_selectquestion');
    }
    if (value.requiredstate === '') {
        errors.push('availability_quizquestion:error_selectstate');
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
