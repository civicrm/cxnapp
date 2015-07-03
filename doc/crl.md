# cxnapp: Certificate Revocation Lists

Certificate Revocation Lists (CRLs) provide a means for flagging certificates as invalid when they
are lost, compromised, or abused.  CRLs are often published by the same certificate authority which
issued them, but these responsibilities may be split across different systems.  In the case of
CiviConnect, the certificate authority is operated in an offline, air-gapped fashion, and
maintaining a CRL from the same system would be prohibtively difficult or untimely, so the CRL is
operated by a separate, online system.  That system is included with `cxnapp` (`CiviCxnCrlBundle`).

## Setup

Determine the name of the certificate authority. This often has two parts:

 * A short name, commonly used in file names (eg `MyRootCA` or file `MyRootCA.crt`)
 * A distinguished name, used in the root certificate (eg `C=US, ST=California, O=My Org, CN=My Root CA`)

To setup a skeletal system, run the `crl:init` command, e.g.

```
$ ./app/console crl:init MyRootCA 'C=US, ST=California, O=My Org, CN=My Root CA'
Create key file (app/crl/MyRootCA/keys.json)
Create demo CA file (app/crl/MyRootCA/ca.crt)
Create certificate request (app/crl/MyRootCA/crldist.csr)
Create certificate self-signed (app/crl/MyRootCA/crldist.crt)
Create revocations file (app/crl/MyRootCA/revocations.yml)
```

The certificates in the skeletal system are internally consistent, but they will not be
trusted by the outside world.  To go into production:

 * Transmit `crldist.csr` to the true certificate authority
 * Sign it
 * Verify that the new certificate is well-formed:
   * The usage extension `CRL Sign` is enabled.
   * The usage extension `Certificate Sign` is **not** enabled.
   * The `Subject DN` matches the CA.
 * Copy the new certificate to `crldist.crt`
 * Copy the true root certificate to `ca.crt`

## Usage: Revoke a Certificate

To revoke a certificate, edit `revocations.yml` and add a clause under `certs`
with the
   * Certificate serial number
     * All cert numbers are interpreted as decimal by default. To use hexadecimal, include at least one colon delimiter.
   * Revocation reason
     * unused
     * keyCompromise
     * cACompromise
     * affiliationChanged
     * superseded
     * cessationOfOperation
     * certificateHold
     * privilegeWithdrawn
     * aACompromise

Example:

```yaml
certs:
  '1234': 'revoked'
  'a1:b2:c3:d4:e5:f6:78': 'privilegeWithdrawn'
```

## Usage: Download a CRL

The `cxnapp` returns three files. If the app is deployed at `http://localhost:8000` and if the
CA is named `MyRootCA`, then these URLs will be available:

 * `http://localhost:8000/ca/MyRootCA.crl` - The current revocation list.
 * `http://localhost:8000/ca/MyRootCA/dist.crt` - The certificate which signs the CRL.
 * `http://localhost:8000/ca/MyRootCA/ca.crt` - The certificate authority for whom certificates are signed.

