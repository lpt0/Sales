<?php
  /*
    Haran
    WEBD3201
    September 15th, 2020
  */
  /* Due to the fact that my Chrome installation doesn't set the Referrer
    header properly, the current URL will be passed fully to the validator,
    instead of expecting it to check the referrer header (which may be wrong).
    Also, in incognito mode, I don't think that the referrer is set.
  */
?>

            
            </div>
            <footer>
                <p class="mt-5 mb-3 text-muted text-center">
                  &copy; 2020
                  <!-- HTML validator -->
                  <br />
                  <a class="text-muted" href="http://validator.w3.org/check?uri=<?php echo getFullUrl(); ?>">Nu Html Checker by W3</a>
                  <!-- Privacy policy -->
                  <br />
                  <a class="text-muted" href="./privacy-policy.php">Privacy Policy</a>
                </p>
            </footer>
        </main>  
       </div>
    </div>
  </body>
</html>
<?php
  // Flush the buffer (sending it to the user)
  ob_flush();
?>