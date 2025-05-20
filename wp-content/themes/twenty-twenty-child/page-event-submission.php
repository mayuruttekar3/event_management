<?php
/**
 * Template Name: Event Submission
 */
get_header();
?>

<?php
	if ( get_option('maintenance_flag') ) {
?>
		<div class="maintenance-message">
			<p>The event form is currently disabled due to maintenance. Please check back later.</p>
		</div>
<?php
	} else {
?>
		<div class="event-submission-form-wrapper">
		  <h2>Submit Your Event</h2>
		  <form id="eventSubmissionForm" enctype="multipart/form-data">

			<!-- Spam check -->
			<input type="text" name="easter_egg_note" style="display:none" autocomplete="off">

			<label>Title: <input type="text" name="event_title" required></label><br>

			<label>Event Start: <input type="text" name="event_start" id="event_start" required></label><br>
			<label>Event End: <input type="text" name="event_end" id="event_end" required></label><br>
			
			<!-- Country -->
			<label>Country:
			<select id="country" name="country" required></select>
			</label><br>

			<!-- State -->
			<label>State:
			<select id="state" name="state" required></select>
			</label><br>

			<!-- City -->
			<label>City:
			<select id="city" name="city" required></select>
			</label><br>

			<label>Organizer Name: <input type="text" name="organizer_name" required></label><br>
			<label>Organizer Email: <input type="email" name="organizer_email" required></label><br>
			<label>Organizer Phone: <input type="text" name="organizer_phone" required></label><br>

			<label><input type="checkbox" name="is_online" id="is_online"> Online Event</label><br>

			<div id="venueDetails">
			  <label>Venue Name: <input type="text" name="venue"></label><br>
			  <label>Latitude: <input type="text" name="lat"></label><br>
			  <label>Longitude: <input type="text" name="lng"></label><br>
			</div>

			<label>Ticket Price: <input type="number" step="0.01" name="ticket_price" required></label><br>

			<label>Event Image (JPG/PNG, Max 2MB): <input type="file" name="event_image" required></label><br>

			<button type="submit">Submit</button>
			<div id="formStatus"></div>
		  </form>
		</div>
<?php
	} 
?>
<script>

/* Frontend submit event form city dropdown start */
document.addEventListener('DOMContentLoaded', function () {
  const countrySelect = document.getElementById('country');
  const stateSelect = document.getElementById('state');
  const citySelect = document.getElementById('city');

  const hierarchy = EventFormData.cityHierarchy;

  // Populate countries
  countrySelect.innerHTML = '<option value="">Select Country</option>';
  hierarchy.forEach(country => {
    const option = document.createElement('option');
    option.value = country.id;
    option.textContent = country.name;
    countrySelect.appendChild(option);
  });

  countrySelect.addEventListener('change', function () {
    const countryId = parseInt(this.value);
    const selectedCountry = hierarchy.find(c => c.id === countryId);

    stateSelect.innerHTML = '<option value="">Select State</option>';
    citySelect.innerHTML = '<option value="">Select City</option>';

    if (selectedCountry) {
      selectedCountry.states.forEach(state => {
        const option = document.createElement('option');
        option.value = state.id;
        option.textContent = state.name;
        stateSelect.appendChild(option);
      });
    }
  });

  stateSelect.addEventListener('change', function () {
    const stateId = parseInt(this.value);
    const selectedCountry = hierarchy.find(c => c.id == countrySelect.value);
    const selectedState = selectedCountry?.states.find(s => s.id === stateId);

    citySelect.innerHTML = '<option value="">Select City</option>';

    if (selectedState) {
      selectedState.cities.forEach(city => {
        const option = document.createElement('option');
        option.value = city.id;
        option.textContent = city.name;
        citySelect.appendChild(option);
      });
    }
  });
});

/* Frontend submit event form city dropdown end */
	
	
document.addEventListener('DOMContentLoaded', function () {
	const form = document.getElementById('eventSubmissionForm');
	const status = document.getElementById('formStatus');
	const venueDiv = document.getElementById('venueDetails');
	const onlineCheckbox = document.getElementById('is_online');

	// Show/hide venue fields
	onlineCheckbox.addEventListener('change', function () {
		venueDiv.style.display = this.checked ? 'none' : 'block';
	});
	
	// validation function
	function validateForm(data) {
		start = new Date(data.get('event_start'));
		end = new Date(data.get('event_end'));
		now = new Date();
		phone = data.get('organizer_phone');
		file = form.querySelector('input[name="event_image"]').files[0];

		// Phone validation
		if (!/^\+91-\d{3}-\d{3}-\d{4}$/.test(phone)) {
			return "Phone must be in format +91-XXX-XXX-XXXX.";
		}

		// Image validation
		if (!file) {
			return "Please upload an image.";
		}
		if (!['image/jpeg', 'image/png'].includes(file.type)) {
			return "Only JPG or PNG allowed.";
		}
		if (file.size > 2 * 1024 * 1024) {
			return "Image must be under 2MB.";
		}

		return null;
	}

	function submitForm(retries = 1) {
		
		formData = new FormData(form);
		error = validateForm(formData);
		
		if (error) {
			return status.textContent = error;
		}

		fetch('<?php echo esc_url(rest_url('v1/events/submit')); ?>', {
		  method: 'POST',
		  headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>' },
		  body: formData
		})
		.then(res => res.json())
		.then(response => {
		  if (response.success) {
			status.textContent = "Event submitted successfully!";
			form.reset();
		  } else {
			if (retries > 0) {
			  console.log("Retrying...");
			  submitForm(retries - 1);
			} else {
			  status.textContent = response.message || "Submission failed.";
			}
		  }
		})
		.catch(() => {
		  if (retries > 0) {
			console.log("Network issue. Retrying...");
			submitForm(retries - 1);
		  } else {
			status.textContent = "AJAX submission failed.";
		  }
		});
	}

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		submitForm(1);
	});
});
</script>

<?php get_footer(); ?>
