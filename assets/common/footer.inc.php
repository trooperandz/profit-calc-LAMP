<!--<div class="container">
	<div class="row">
		<div class="col-md-12">
			<p>&nbsp;</p>
		</div>
		<div class="col-md-12">
			<p>&nbsp;</p>
		</div>
	</div>
</div>-->
<div class="footer-push"></div> <!-- Move the footer down, if necessary.  Used on Admin & My Docs pages. -->
<footer class="footer">
	<div class="container-fluid">
		<p class="text-muted"><span class="footer-copyright"> &copy; <?php echo date('Y');?></span> Service Operations Specialists, Inc.</p>
	</div>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="assets/js/jquery.validDate.js"></script>
<script src="assets/js/jquery.dateZoom.js"></script>
<script src="assets/js/init.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/jquery-ui.min.js"></script>
<script src="assets/js/fileinput.min.js"></script>
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.12/js/jquery.dataTables.js"></script>
<!--<script src="assets/js/dataTables.min.js"></script>-->
<!--<script src="assets/js/dataTables.bootstrap.min.js"></script>-->
<script>
	$(function() {
		// Initialize jQuery UI tabs
		$("#tabs").tabs();
		
		// Initialize datepicker
		$("#date_select").datepicker();
		
		// Initialize dataTables
		$("#user_table").DataTable();
		$("#user_doc_table").DataTable();
		
		// Initialize Bootstrap fileinput plugin
		$("#file_input").fileinput();
		/*
		$( "#progressbar" ).progressbar({
			value: 37
		});
		var progressbar = $("#progressbar"),
            progressLabel = $(".progress-label");

		progressbar.progressbar({
          value: false,
          change: function () {
              progressLabel.text(progressbar.progressbar("value") + "%");
          },
          complete: function () {
              progressLabel.text("Complete!");
          }
		});

		function progress() {
          var val = progressbar.progressbar("value") || 0;

          progressbar.progressbar("value", val + 1);

          if (val < 99) {
              setTimeout(progress, 100);
          }
		}
		setTimeout(progress, 3000);
		*/
	});
</script>

</body>

</html>