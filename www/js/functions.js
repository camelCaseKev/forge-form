function loadLogin() {
	$(document).ready(function() {
		var myModal = new bootstrap.Modal(document.getElementById('myModal'), { backdrop: false });
		myModal.show();
		document.getElementById("signin").classList.add('show');
	});
}


function logout() {
	localStorage.removeItem('user_hash');
	location.reload();
}


function buildExercise(num) { // num is appended to IDs to uniquely identify the exercise and its contents
	var str = "";
	str += "<div class='exercise mb-4 p-3 border rounded' id='exercise"+num+"'>"+
	"<div class='mb-3'><label for='exName"+num+"' class='form-label'>Exercise Name</label><input type='text' class='form-control exName' id='exName"+num+"' name='exName"+num+"'></div>"+
	"<div class='row mb-3'><div class='col'><label for='exSets"+num+"' class='form-label'>Sets</label><input type='number' class='form-control input-small exSets' id='exSets"+num+"' name='exSets"+num+"' min='0' max='99'></div>"+
	"<div class='col'><label for='exReps"+num+"' class='form-label'>Reps</label><input type='number' class='form-control input-small exReps' id='exReps"+num+"' name='exReps"+num+"' min='0' max='99'></div></div>"+
	"<div class='row mb-3'><div class='col'><label for='exWeight"+num+"' class='form-label'>Last working weight</label><input type='text' class='form-control input-medium exWeight' id='exWeight"+num+"' name='exWeight"+num+"'></div>"+
	"<div class='col'><label for='exRestTime"+num+"' class='form-label'>Rest Time</label>"+
	"<select class='form-select input-medium exRestTime' id='exRestTime"+num+"' name='exRestTime"+num+"'>"+
	"<option value='30'>30s</option>"+
	"<option value='60'>60s</option>"+
	"<option value='90'>90s</option>"+
	"<option value='120'>120s</option>"+
	"<option value='180'>180s</option>"+
	"</select>"+
	"<a id='exTimerBtn"+num+"' class='btn settings-btn'>Start</a>"+
	"</div></div>"+
	"<div class='mb-3'><label for='exNotes"+num+"' class='form-label'>Notes</label><textarea class='form-control exNotes' id='exNotes"+num+"' name='exNotes"+num+"'></textarea></div>"+
	"</div>";
	
	return str;
}


function loadPrograms() {
	var container = $('#workout-names');
	container.html('');

	var formData = {
		hash: user_hash,
		type: 'get-programs'
	};

	$.post(domain + '/ff_formprocess.php', formData, function(res) {
		var obj = JSON.parse(res);
		if(obj.success === 1) {
			obj.programs.forEach(p => {
				var btn = $("<a href='#' class='btn program-btn w-100'>"+p.name+"</a>");
				btn.on('click', function() {
					loadProgramByName(p.name);
				});
				container.append(btn);
			});
		}
	});
}


function loadProgramByName(programName) {
	var formData = {
		hash: user_hash,
		type: 'get-program-by-name',
		programName: programName
	};

	$.post(domain + '/ff_formprocess.php', formData, function(res) {
		var obj = JSON.parse(res);
		if(obj.success === 1) {
			$('#page-home').hide();
			$('#page-exercises').show();
			$('#programName').val(programName);

			var container = $('#exercise-container');
			container.html('');

			obj.exercises.forEach((ex, i) => {
				var exHTML = buildExercise(i + 1);
				container.append(exHTML);

				var block = container.find('#exercise'+(i + 1));
				block.find('.exName').val(ex.name);
				block.find('.exSets').val(ex.sets);
				block.find('.exReps').val(ex.reps);
				block.find('.exWeight').val(ex.last_weight);
				block.find('.exRestTime').val(ex.rest_time_index);
				block.find('.exNotes').val(ex.notes);
			});
		}
	});
}
