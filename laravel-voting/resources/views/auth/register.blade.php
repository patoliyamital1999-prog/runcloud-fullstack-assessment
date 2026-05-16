<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Voting System</title>
  <!-- base:css -->
  <link rel="stylesheet" href="../../vendors/typicons/typicons.css">
  <link rel="stylesheet" href="../../vendors/css/vendor.bundle.base.css">

  <link rel="stylesheet" href="../../css/vertical-layout-light/style.css">

</head>

<body>
  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="content-wrapper d-flex align-items-center auth px-0">
        <div class="row w-100 mx-0">
          <div class="col-lg-4 mx-auto">
            @if (session('success'))
            <div class="alert alert-success">
              {{ session('success') }}
            </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            <div class="auth-form-light text-left py-5 px-4 px-sm-5">
              <div class="text-center" style="font-size: 37px;margin-bottom: 6px;">
                <h3 class="mb-4 text-center">Sign Up</h3>
              </div>

              <form action="{{ route('register') }}" method="POST">
                @csrf
                <div class="form-group">
                  <label>First Name</label>
                  <input type="text" name="name" class="form-control" value="{{ old('name') }}">
                  @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                  <label>Email</label>
                  <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                  @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                  <label>Mobile Number</label>
                  <input type="number" name="mobile_no" class="form-control" value="{{ old('mobile_no') }}">
                  @error('mobile_no') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                  <label>Password</label>
                  <input type="password" name="password" class="form-control">
                  @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="text-center mt-3">
                  <small>
                      Already have an account? 
                      <a href="{{ route('login') }}" class="text-primary"><b>Login here</b></a>
                  </small>
              </div><br>

                <div class="mb-2">
                  <button type="submit" class="btn btn-block btn-facebook auth-form-btn"><b>Register</b></button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- content-wrapper ends -->
  </div>
  <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->
  <!-- base:js -->
  <script src="../../vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- inject:js -->
  <script src="../../js/off-canvas.js"></script>
  <script src="../../js/hoverable-collapse.js"></script>
  <script src="../../js/template.js"></script>
  <script src="../../js/settings.js"></script>
  <script src="../../js/todolist.js"></script>
  <script>
    // Auto-hide all alerts after 3 seconds
    setTimeout(function() {
        const successAlerts = document.querySelectorAll('.alert-success');
        successAlerts.forEach(function(alert) {
            alert.style.display = 'none';
        });

        const errorAlerts = document.querySelectorAll('.alert-danger');
        errorAlerts.forEach(function(alert) {
            alert.style.display = 'none';
        });
    }, 3000); // 3 seconds
</script>
  <!-- endinject -->
</body>

</html>