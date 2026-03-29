



<div>
    <form action="{{ route('reset.password') }}" method="POST">

        @csrf
        <input type="hidden" name="token" value="{{ $data['token'] }}">

        <input type="hidden" name="email" value="{{ $data['email'] }}">




        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Nuova Password</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <input type="password" id="password" class="form-control" name="password" placeholder="*******" required autofocus>




                    </div>
                </div>


            </div>
        </div>

        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Conferma Password</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <input type="password" id="password-confirm" class="form-control" name="password_confirmation" placeholder="*******" required autofocus>




                    </div>
                </div>


            </div>
        </div>
        <hr>

        <div class="field is-horizontal">
            <div class="field-label is-normal"></div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <button type="submit" class="button is-primary">
                           Reset Password

                        </button>

                    </div>
                </div>
            </div>


        </div>

    </form>
</div>
