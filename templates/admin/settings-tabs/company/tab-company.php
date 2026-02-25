<?php

use MydPro\Includes\Myd_Legacy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check legacy WhatsApp number
 */
$whatsapp_phone = get_option( 'myd-business-whatsapp' );
if ( empty( $whatsapp_phone ) ) {
	$whatsapp_phone = Myd_Legacy::get_old_whatsapp();
}

?>
<div id="tab-company-content" class="myd-tabs-content myd-tabs-content--active">
	<h2>
		<?php esc_html_e( 'Company Settings', 'myd-delivery-pro' ); ?>
	</h2>
	<p>
		<?php esc_html_e( 'In this section you can configure all the company settings.', 'myd-delivery-pro' ); ?>
	</p>

	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="fdm-business-name"><?php esc_html_e( 'Company Name', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<input name="fdm-business-name" type="text" id="fdm-business-name" value="<?php echo esc_attr( get_option( 'fdm-business-name' ) ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'The company name can be used in custom message or others parts of template/site.', 'myd-delivery-pro' );?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="myd-business-whatsapp"><?php esc_html_e( 'WhatsApp', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<input
						name="myd-business-whatsapp"
						type="text"
						id="myd-business-whatsapp"
						value="<?php echo esc_attr( $whatsapp_phone ); ?>"
						class="regular-text"
						data-mask="+## ############"
						inputmode="numeric"
					>
					<p class="description">
						<?php esc_html_e( 'This phone can be used in custom message or other parts of template/site.', 'myd-delivery-pro' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="myd-business-mail"><?php esc_html_e( 'Email', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<input name="myd-business-mail" type="email" id="myd-business-mail" value="<?php echo esc_attr( get_option( 'myd-business-mail' ) ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'This email can be used in custom message or other parts of template/site.', 'myd-delivery-pro' );?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="myd-business-address"><?php esc_html_e( 'Endereço', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<input
						name="myd-business-address"
						type="text"
						id="myd-business-address"
						value="<?php echo esc_attr( get_option( 'myd-business-address' ) ); ?>"
						class="regular-text"
						required
					>
					<p class="description">
						<?php esc_html_e( 'Informe o endereço completo da loja. Campo obrigatório.', 'myd-delivery-pro' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="fdm-business-country"><?php esc_html_e( 'Country', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<select name="fdm-business-country" id="fdm-business-country">
						<option value=""><?php esc_html_e( 'Select', 'myd-delivery-pro' );?></option>
						<option value="United States" <?php selected( get_option('fdm-business-country'), 'United States' ); ?> >United States</option>
						<option value="United Kingdom" <?php selected( get_option('fdm-business-country'), 'United Kingdom' ); ?> >United Kingdom</option>
						<option value="Afghanistan" <?php selected( get_option('fdm-business-country'), 'Afghanistan' ); ?> >Afghanistan</option>
						<option value="Albania" <?php selected( get_option('fdm-business-country'), 'Albania' ); ?> >Albania</option>
						<option value="Algeria" <?php selected( get_option('fdm-business-country'), 'Algeria' ); ?> >Algeria</option>
						<option value="American Samoa" <?php selected( get_option('fdm-business-country'), 'American Samoa' ); ?> >American Samoa</option>
						<option value="Andorra" <?php selected( get_option('fdm-business-country'), 'Andorra' ); ?> >Andorra</option>
						<option value="Angola" <?php selected( get_option('fdm-business-country'), 'Angola' ); ?> >Angola</option>
						<option value="Anguilla" <?php selected( get_option('fdm-business-country'), 'Anguilla' ); ?> >Anguilla</option>
						<option value="Antarctica" <?php selected( get_option('fdm-business-country'), 'Antarctica' ); ?> >Antarctica</option>
						<option value="Antigua and Barbuda" <?php selected( get_option('fdm-business-country'), 'Antigua and Barbuda' ); ?> >Antigua and Barbuda</option>
						<option value="Argentina" <?php selected( get_option('fdm-business-country'), 'Argentina' ); ?> >Argentina</option>
						<option value="Armenia" <?php selected( get_option('fdm-business-country'), 'Armenia' ); ?> >Armenia</option>
						<option value="Aruba" <?php selected( get_option('fdm-business-country'), 'Aruba' ); ?> >Aruba</option>
						<option value="Australia" <?php selected( get_option('fdm-business-country'), 'Australia' ); ?> >Australia</option>
						<option value="Austria" <?php selected( get_option('fdm-business-country'), 'Austria' ); ?> >Austria</option>
						<option value="Azerbaijan" <?php selected( get_option('fdm-business-country'), 'Azerbaijan' ); ?> >Azerbaijan</option>
						<option value="Bahamas" <?php selected( get_option('fdm-business-country'), 'Bahamas' ); ?> >Bahamas</option>
						<option value="Bahrain" <?php selected( get_option('fdm-business-country'), 'Bahrain' ); ?> >Bahrain</option>
						<option value="Bangladesh" <?php selected( get_option('fdm-business-country'), 'Bangladesh' ); ?> >Bangladesh</option>
						<option value="Barbados" <?php selected( get_option('fdm-business-country'), 'Barbados' ); ?> >Barbados</option>
						<option value="Belarus" <?php selected( get_option('fdm-business-country'), 'Belarus' ); ?> >Belarus</option>
						<option value="Belgium" <?php selected( get_option('fdm-business-country'), 'Belgium' ); ?> >Belgium</option>
						<option value="Belize" <?php selected( get_option('fdm-business-country'), 'Belize' ); ?> >Belize</option>
						<option value="Benin" <?php selected( get_option('fdm-business-country'), 'Benin' ); ?> >Benin</option>
						<option value="Bermuda" <?php selected( get_option('fdm-business-country'), 'Bermuda' ); ?> >Bermuda</option>
						<option value="Bhutan" <?php selected( get_option('fdm-business-country'), 'Bhutan' ); ?> >Bhutan</option>
						<option value="Bolivia" <?php selected( get_option('fdm-business-country'), 'Bolivia' ); ?> >Bolivia</option>
						<option value="Bosnia and Herzegovina" <?php selected( get_option('fdm-business-country'), 'Bosnia and Herzegovina' ); ?> >Bosnia and Herzegovina</option>
						<option value="Botswana" <?php selected( get_option('fdm-business-country'), 'Botswana' ); ?> >Botswana</option>
						<option value="Bouvet Island" <?php selected( get_option('fdm-business-country'), 'Bouvet Island' ); ?> >Bouvet Island</option>
						<option value="Brazil" <?php selected( get_option('fdm-business-country'), 'Brazil' ); ?> >Brazil</option>
						<option value="British Indian Ocean Territory" <?php selected( get_option('fdm-business-country'), 'British Indian Ocean Territory' ); ?> >British Indian Ocean Territory</option>
						<option value="Brunei Darussalam" <?php selected( get_option('fdm-business-country'), 'Brunei Darussalam' ); ?> >Brunei Darussalam</option>
						<option value="Bulgaria" <?php selected( get_option('fdm-business-country'), 'Bulgaria' ); ?> >Bulgaria</option>
						<option value="Burkina Faso" <?php selected( get_option('fdm-business-country'), 'Burkina Faso' ); ?> >Burkina Faso</option>
						<option value="Burundi" <?php selected( get_option('fdm-business-country'), 'Burundi' ); ?> >Burundi</option>
						<option value="Cambodia" <?php selected( get_option('fdm-business-country'), 'Cambodia' ); ?> >Cambodia</option>
						<option value="Cameroon" <?php selected( get_option('fdm-business-country'), 'Cameroon' ); ?> >Cameroon</option>
						<option value="Canada" <?php selected( get_option('fdm-business-country'), 'Canada' ); ?> >Canada</option>
						<option value="Cape Verde" <?php selected( get_option('fdm-business-country'), 'Cape Verde' ); ?> >Cape Verde</option>
						<option value="Cayman Islands" <?php selected( get_option('fdm-business-country'), 'Cayman Islands' ); ?> >Cayman Islands</option>
						<option value="Central African Republic" <?php selected( get_option('fdm-business-country'), 'Central African Republic' ); ?> >Central African Republic</option>
						<option value="Chad" <?php selected( get_option('fdm-business-country'), 'Chad' ); ?> >Chad</option>
						<option value="Chile" <?php selected( get_option('fdm-business-country'), 'Chile' ); ?> >Chile</option>
						<option value="China" <?php selected( get_option('fdm-business-country'), 'China' ); ?> >China</option>
						<option value="Christmas Island" <?php selected( get_option('fdm-business-country'), 'Christmas Island' ); ?> >Christmas Island</option>
						<option value="Cocos (Keeling) Islands" <?php selected( get_option('fdm-business-country'), 'Cocos (Keeling) Islands' ); ?> >Cocos (Keeling) Islands</option>
						<option value="Colombia" <?php selected( get_option('fdm-business-country'), 'Colombia' ); ?> >Colombia</option>
						<option value="Comoros" <?php selected( get_option('fdm-business-country'), 'Comoros' ); ?> >Comoros</option>
						<option value="Congo" <?php selected( get_option('fdm-business-country'), 'Congo' ); ?> >Congo</option>
						<option value="Congo, The Democratic Republic of The" <?php selected( get_option('fdm-business-country'), 'Congo, The Democratic Republic of The' ); ?> >Congo, The Democratic Republic of The</option>
						<option value="Cook Islands" <?php selected( get_option('fdm-business-country'), 'Cook Islands' ); ?> >Cook Islands</option>
						<option value="Costa Rica" <?php selected( get_option('fdm-business-country'), 'Costa Rica' ); ?> >Costa Rica</option>
						<option value="Cote D'ivoire" <?php selected( get_option('fdm-business-country'), "Cote D'ivoire" ); ?> >Cote D'ivoire</option>
						<option value="Croatia" <?php selected( get_option('fdm-business-country'), 'Croatia' ); ?> >Croatia</option>
						<option value="Cuba" <?php selected( get_option('fdm-business-country'), 'Cuba' ); ?> >Cuba</option>
						<option value="Cyprus" <?php selected( get_option('fdm-business-country'), 'Cyprus' ); ?> >Cyprus</option>
						<option value="Czech Republic" <?php selected( get_option('fdm-business-country'), 'Czech Republic' ); ?> >Czech Republic</option>
						<option value="Denmark" <?php selected( get_option('fdm-business-country'), 'Denmark' ); ?> >Denmark</option>
						<option value="Djibouti" <?php selected( get_option('fdm-business-country'), 'Djibouti' ); ?> >Djibouti</option>
						<option value="Dominica" <?php selected( get_option('fdm-business-country'), 'Dominica' ); ?> >Dominica</option>
						<option value="Dominican Republic" <?php selected( get_option('fdm-business-country'), 'Dominican Republic' ); ?> >Dominican Republic</option>
						<option value="Ecuador" <?php selected( get_option('fdm-business-country'), 'Ecuador' ); ?> >Ecuador</option>
						<option value="Egypt" <?php selected( get_option('fdm-business-country'), 'Egypt' ); ?> >Egypt</option>
						<option value="El Salvador" <?php selected( get_option('fdm-business-country'), 'El Salvador' ); ?> >El Salvador</option>
						<option value="Equatorial Guinea" <?php selected( get_option('fdm-business-country'), 'Equatorial Guinea' ); ?> >Equatorial Guinea</option>
						<option value="Eritrea" <?php selected( get_option('fdm-business-country'), 'Eritrea' ); ?> >Eritrea</option>
						<option value="Estonia" <?php selected( get_option('fdm-business-country'), 'Estonia' ); ?> >Estonia</option>
						<option value="Ethiopia" <?php selected( get_option('fdm-business-country'), 'Ethiopia' ); ?> >Ethiopia</option>
						<option value="Falkland Islands (Malvinas)" <?php selected( get_option('fdm-business-country'), 'Falkland Islands (Malvinas)' ); ?> >Falkland Islands (Malvinas)</option>
						<option value="Faroe Islands" <?php selected( get_option('fdm-business-country'), 'Faroe Islands' ); ?> >Faroe Islands</option>
						<option value="Fiji" <?php selected( get_option('fdm-business-country'), 'Fiji' ); ?> >Fiji</option>
						<option value="Finland" <?php selected( get_option('fdm-business-country'), 'Finland' ); ?> >Finland</option>
						<option value="France" <?php selected( get_option('fdm-business-country'), 'France' ); ?> >France</option>
						<option value="French Guiana" <?php selected( get_option('fdm-business-country'), 'French Guiana' ); ?> >French Guiana</option>
						<option value="French Polynesia" <?php selected( get_option('fdm-business-country'), 'French Polynesia' ); ?> >French Polynesia</option>
						<option value="French Southern Territories" <?php selected( get_option('fdm-business-country'), 'French Southern Territories' ); ?> >French Southern Territories</option>
						<option value="Gabon" <?php selected( get_option('fdm-business-country'), 'Gabon' ); ?> >Gabon</option>
						<option value="Gambia" <?php selected( get_option('fdm-business-country'), 'Gambia' ); ?> >Gambia</option>
						<option value="Georgia" <?php selected( get_option('fdm-business-country'), 'Georgia' ); ?> >Georgia</option>
						<option value="Germany" <?php selected( get_option('fdm-business-country'), 'Germany' ); ?> >Germany</option>
						<option value="Ghana" <?php selected( get_option('fdm-business-country'), 'Ghana' ); ?> >Ghana</option>
						<option value="Gibraltar" <?php selected( get_option('fdm-business-country'), 'Gibraltar' ); ?> >Gibraltar</option>
						<option value="Greece" <?php selected( get_option('fdm-business-country'), 'Greece' ); ?> >Greece</option>
						<option value="Greenland" <?php selected( get_option('fdm-business-country'), 'Greenland' ); ?> >Greenland</option>
						<option value="Grenada" <?php selected( get_option('fdm-business-country'), 'Grenada' ); ?> >Grenada</option>
						<option value="Guadeloupe" <?php selected( get_option('fdm-business-country'), 'Guadeloupe' ); ?> >Guadeloupe</option>
						<option value="Guam" <?php selected( get_option('fdm-business-country'), 'Guam' ); ?> >Guam</option>
						<option value="Guatemala" <?php selected( get_option('fdm-business-country'), 'Guatemala' ); ?> >Guatemala</option>
						<option value="Guinea" <?php selected( get_option('fdm-business-country'), 'Guinea' ); ?> >Guinea</option>
						<option value="Guinea-bissau" <?php selected( get_option('fdm-business-country'), 'Guinea-bissau' ); ?> >Guinea-bissau</option>
						<option value="Guyana" <?php selected( get_option('fdm-business-country'), 'Guyana' ); ?> >Guyana</option>
						<option value="Haiti" <?php selected( get_option('fdm-business-country'), 'Haiti' ); ?> >Haiti</option>
						<option value="Heard Island and Mcdonald Islands" <?php selected( get_option('fdm-business-country'), 'Heard Island and Mcdonald Islands' ); ?> >Heard Island and Mcdonald Islands</option>
						<option value="Holy See (Vatican City State)" <?php selected( get_option('fdm-business-country'), 'Holy See (Vatican City State)' ); ?> >Holy See (Vatican City State)</option>
						<option value="Honduras" <?php selected( get_option('fdm-business-country'), 'Honduras' ); ?> >Honduras</option>
						<option value="Hong Kong" <?php selected( get_option('fdm-business-country'), 'Hong Kong' ); ?> >Hong Kong</option>
						<option value="Hungary" <?php selected( get_option('fdm-business-country'), 'Hungary' ); ?> >Hungary</option>
						<option value="Iceland" <?php selected( get_option('fdm-business-country'), 'Iceland' ); ?> >Iceland</option>
						<option value="India" <?php selected( get_option('fdm-business-country'), 'India' ); ?> >India</option>
						<option value="Indonesia" <?php selected( get_option('fdm-business-country'), 'Indonesia' ); ?> >Indonesia</option>
						<option value="Iran, Islamic Republic of" <?php selected( get_option('fdm-business-country'), 'Iran, Islamic Republic of' ); ?> >Iran, Islamic Republic of</option>
						<option value="Iraq" <?php selected( get_option('fdm-business-country'), 'Iraq' ); ?> >Iraq</option>
						<option value="Ireland" <?php selected( get_option('fdm-business-country'), 'Ireland' ); ?> >Ireland</option>
						<option value="Israel" <?php selected( get_option('fdm-business-country'), 'Israel' ); ?> >Israel</option>
						<option value="Italy" <?php selected( get_option('fdm-business-country'), 'Italy' ); ?> >Italy</option>
						<option value="Jamaica" <?php selected( get_option('fdm-business-country'), 'Jamaica' ); ?> >Jamaica</option>
						<option value="Japan" <?php selected( get_option('fdm-business-country'), 'Japan' ); ?> >Japan</option>
						<option value="Jordan" <?php selected( get_option('fdm-business-country'), 'Jordan' ); ?> >Jordan</option>
						<option value="Kazakhstan" <?php selected( get_option('fdm-business-country'), 'Kazakhstan' ); ?> >Kazakhstan</option>
						<option value="Kenya" <?php selected( get_option('fdm-business-country'), 'Kenya' ); ?> >Kenya</option>
						<option value="Kiribati" <?php selected( get_option('fdm-business-country'), 'Kiribati' ); ?> >Kiribati</option>
						<option value="Korea, Democratic People's Republic of" <?php selected( get_option('fdm-business-country'), "Korea, Democratic People's Republic of" ); ?> >Korea, Democratic People's Republic of</option>
						<option value="Korea, Republic of" <?php selected( get_option('fdm-business-country'), 'Korea, Republic of' ); ?> >Korea, Republic of</option>
						<option value="Kuwait" <?php selected( get_option('fdm-business-country'), 'Kuwait' ); ?> >Kuwait</option>
						<option value="Kyrgyzstan" <?php selected( get_option('fdm-business-country'), 'Kyrgyzstan' ); ?> >Kyrgyzstan</option>
						<option value="Lao People's Democratic Republic" <?php selected( get_option('fdm-business-country'), "Lao People's Democratic Republic" ); ?> >Lao People's Democratic Republic</option>
						<option value="Latvia" <?php selected( get_option('fdm-business-country'), 'Latvia' ); ?> >Latvia</option>
						<option value="Lebanon" <?php selected( get_option('fdm-business-country'), 'Lebanon' ); ?> >Lebanon</option>
						<option value="Lesotho" <?php selected( get_option('fdm-business-country'), 'Lesotho' ); ?> >Lesotho</option>
						<option value="Liberia" <?php selected( get_option('fdm-business-country'), 'Liberia' ); ?> >Liberia</option>
						<option value="Libyan Arab Jamahiriya" <?php selected( get_option('fdm-business-country'), 'Libyan Arab Jamahiriya' ); ?> >Libyan Arab Jamahiriya</option>
						<option value="Liechtenstein" <?php selected( get_option('fdm-business-country'), 'Liechtenstein' ); ?> >Liechtenstein</option>
						<option value="Lithuania" <?php selected( get_option('fdm-business-country'), 'Lithuania' ); ?> >Lithuania</option>
						<option value="Luxembourg" <?php selected( get_option('fdm-business-country'), 'Luxembourg' ); ?> >Luxembourg</option>
						<option value="Macao" <?php selected( get_option('fdm-business-country'), 'Macao' ); ?> >Macao</option>
						<option value="North Macedonia" <?php selected( get_option('fdm-business-country'), 'North Macedonia' ); ?> >North Macedonia</option>
						<option value="Madagascar" <?php selected( get_option('fdm-business-country'), 'Madagascar' ); ?> >Madagascar</option>
						<option value="Malawi" <?php selected( get_option('fdm-business-country'), 'Malawi' ); ?> >Malawi</option>
						<option value="Malaysia" <?php selected( get_option('fdm-business-country'), 'Malaysia' ); ?> >Malaysia</option>
						<option value="Maldives" <?php selected( get_option('fdm-business-country'), 'Maldives' ); ?> >Maldives</option>
						<option value="Mali" <?php selected( get_option('fdm-business-country'), 'Mali' ); ?> >Mali</option>
						<option value="Malta" <?php selected( get_option('fdm-business-country'), 'Malta' ); ?> >Malta</option>
						<option value="Marshall Islands" <?php selected( get_option('fdm-business-country'), 'Marshall Islands' ); ?> >Marshall Islands</option>
						<option value="Martinique" <?php selected( get_option('fdm-business-country'), 'Martinique' ); ?> >Martinique</option>
						<option value="Mauritania" <?php selected( get_option('fdm-business-country'), 'Mauritania' ); ?> >Mauritania</option>
						<option value="Mauritius" <?php selected( get_option('fdm-business-country'), 'Mauritius' ); ?> >Mauritius</option>
						<option value="Mayotte" <?php selected( get_option('fdm-business-country'), 'Mayotte' ); ?> >Mayotte</option>
						<option value="Mexico" <?php selected( get_option('fdm-business-country'), 'Mexico' ); ?> >Mexico</option>
						<option value="Micronesia, Federated States of" <?php selected( get_option('fdm-business-country'), 'Micronesia, Federated States of' ); ?> >Micronesia, Federated States of</option>
						<option value="Moldova, Republic of" <?php selected( get_option('fdm-business-country'), 'Moldova, Republic of' ); ?> >Moldova, Republic of</option>
						<option value="Monaco" <?php selected( get_option('fdm-business-country'), 'Monaco' ); ?> >Monaco</option>
						<option value="Mongolia" <?php selected( get_option('fdm-business-country'), 'Mongolia' ); ?> >Mongolia</option>
						<option value="Montserrat" <?php selected( get_option('fdm-business-country'), 'Montserrat' ); ?> >Montserrat</option>
						<option value="Morocco" <?php selected( get_option('fdm-business-country'), 'Morocco' ); ?> >Morocco</option>
						<option value="Mozambique" <?php selected( get_option('fdm-business-country'), 'Mozambique' ); ?> >Mozambique</option>
						<option value="Myanmar" <?php selected( get_option('fdm-business-country'), 'Myanmar' ); ?> >Myanmar</option>
						<option value="Namibia" <?php selected( get_option('fdm-business-country'), 'Namibia' ); ?> >Namibia</option>
						<option value="Nauru" <?php selected( get_option('fdm-business-country'), 'Nauru' ); ?> >Nauru</option>
						<option value="Nepal" <?php selected( get_option('fdm-business-country'), 'Nepal' ); ?> >Nepal</option>
						<option value="Netherlands" <?php selected( get_option('fdm-business-country'), 'Netherlands' ); ?> >Netherlands</option>
						<option value="Netherlands Antilles" <?php selected( get_option('fdm-business-country'), 'Netherlands Antilles' ); ?> >Netherlands Antilles</option>
						<option value="New Caledonia" <?php selected( get_option('fdm-business-country'), 'New Caledonia' ); ?> >New Caledonia</option>
						<option value="New Zealand" <?php selected( get_option('fdm-business-country'), 'New Zealand' ); ?> >New Zealand</option>
						<option value="Nicaragua" <?php selected( get_option('fdm-business-country'), 'Nicaragua' ); ?> >Nicaragua</option>
						<option value="Niger" <?php selected( get_option('fdm-business-country'), 'Niger' ); ?> >Niger</option>
						<option value="Nigeria" <?php selected( get_option('fdm-business-country'), 'Nigeria' ); ?> >Nigeria</option>
						<option value="Niue" <?php selected( get_option('fdm-business-country'), 'Niue' ); ?> >Niue</option>
						<option value="Norfolk Island" <?php selected( get_option('fdm-business-country'), 'Norfolk Island' ); ?> >Norfolk Island</option>
						<option value="Northern Mariana Islands" <?php selected( get_option('fdm-business-country'), 'Northern Mariana Islands' ); ?> >Northern Mariana Islands</option>
						<option value="Norway" <?php selected( get_option('fdm-business-country'), 'Norway' ); ?> >Norway</option>
						<option value="Oman" <?php selected( get_option('fdm-business-country'), 'Oman' ); ?> >Oman</option>
						<option value="Pakistan" <?php selected( get_option('fdm-business-country'), 'Pakistan' ); ?> >Pakistan</option>
						<option value="Palau" <?php selected( get_option('fdm-business-country'), 'Palau' ); ?> >Palau</option>
						<option value="Palestinian Territory, Occupied" <?php selected( get_option('fdm-business-country'), 'Palestinian Territory, Occupied' ); ?> >Palestinian Territory, Occupied</option>
						<option value="Panama" <?php selected( get_option('fdm-business-country'), 'Panama' ); ?> >Panama</option>
						<option value="Papua New Guinea" <?php selected( get_option('fdm-business-country'), 'Papua New Guinea' ); ?> >Papua New Guinea</option>
						<option value="Paraguay" <?php selected( get_option('fdm-business-country'), 'Paraguay' ); ?> >Paraguay</option>
						<option value="Peru" <?php selected( get_option('fdm-business-country'), 'Peru' ); ?> >Peru</option>
						<option value="Philippines" <?php selected( get_option('fdm-business-country'), 'Philippines' ); ?> >Philippines</option>
						<option value="Pitcairn" <?php selected( get_option('fdm-business-country'), 'Pitcairn' ); ?> >Pitcairn</option>
						<option value="Poland" <?php selected( get_option('fdm-business-country'), 'Poland' ); ?> >Poland</option>
						<option value="Portugal" <?php selected( get_option('fdm-business-country'), 'Portugal' ); ?> >Portugal</option>
						<option value="Puerto Rico" <?php selected( get_option('fdm-business-country'), 'Puerto Rico' ); ?> >Puerto Rico</option>
						<option value="Qatar" <?php selected( get_option('fdm-business-country'), 'Qatar' ); ?> >Qatar</option>
						<option value="Reunion" <?php selected( get_option('fdm-business-country'), 'Reunion' ); ?> >Reunion</option>
						<option value="Romania" <?php selected( get_option('fdm-business-country'), 'Romania' ); ?> >Romania</option>
						<option value="Russian Federation" <?php selected( get_option('fdm-business-country'), 'Russian Federation' ); ?> >Russian Federation</option>
						<option value="Rwanda" <?php selected( get_option('fdm-business-country'), 'Rwanda' ); ?> >Rwanda</option>
						<option value="Saint Helena" <?php selected( get_option('fdm-business-country'), 'Saint Helena' ); ?> >Saint Helena</option>
						<option value="Saint Kitts and Nevis" <?php selected( get_option('fdm-business-country'), 'Saint Kitts and Nevis' ); ?> >Saint Kitts and Nevis</option>
						<option value="Saint Lucia" <?php selected( get_option('fdm-business-country'), 'Saint Lucia' ); ?> >Saint Lucia</option>
						<option value="Saint Pierre and Miquelon" <?php selected( get_option('fdm-business-country'), 'Saint Pierre and Miquelon' ); ?> >Saint Pierre and Miquelon</option>
						<option value="Saint Vincent and The Grenadines" <?php selected( get_option('fdm-business-country'), 'Saint Vincent and The Grenadines' ); ?> >Saint Vincent and The Grenadines</option>
						<option value="Samoa" <?php selected( get_option('fdm-business-country'), 'Samoa' ); ?> >Samoa</option>
						<option value="San Marino" <?php selected( get_option('fdm-business-country'), 'San Marino' ); ?> >San Marino</option>
						<option value="Sao Tome and Principe" <?php selected( get_option('fdm-business-country'), 'Sao Tome and Principe' ); ?> >Sao Tome and Principe</option>
						<option value="Saudi Arabia" <?php selected( get_option('fdm-business-country'), 'Saudi Arabia' ); ?> >Saudi Arabia</option>
						<option value="Senegal" <?php selected( get_option('fdm-business-country'), 'Senegal' ); ?> >Senegal</option>
						<option value="Serbia and Montenegro" <?php selected( get_option('fdm-business-country'), 'Serbia and Montenegro' ); ?> >Serbia and Montenegro</option>
						<option value="Seychelles" <?php selected( get_option('fdm-business-country'), 'Seychelles' ); ?> >Seychelles</option>
						<option value="Sierra Leone" <?php selected( get_option('fdm-business-country'), 'Sierra Leone' ); ?> >Sierra Leone</option>
						<option value="Singapore" <?php selected( get_option('fdm-business-country'), 'Singapore' ); ?> >Singapore</option>
						<option value="Slovakia" <?php selected( get_option('fdm-business-country'), 'Slovakia' ); ?> >Slovakia</option>
						<option value="Slovenia" <?php selected( get_option('fdm-business-country'), 'Slovenia' ); ?> >Slovenia</option>
						<option value="Solomon Islands" <?php selected( get_option('fdm-business-country'), 'Solomon Islands' ); ?> >Solomon Islands</option>
						<option value="Somalia" <?php selected( get_option('fdm-business-country'), 'Somalia' ); ?> >Somalia</option>
						<option value="South Africa" <?php selected( get_option('fdm-business-country'), 'South Africa' ); ?> >South Africa</option>
						<option value="South Georgia and The South Sandwich Islands" <?php selected( get_option('fdm-business-country'), 'South Georgia and The South Sandwich Islands' ); ?> >South Georgia and The South Sandwich Islands</option>
						<option value="Spain" <?php selected( get_option('fdm-business-country'), 'Spain' ); ?> >Spain</option>
						<option value="Sri Lanka" <?php selected( get_option('fdm-business-country'), 'Sri Lanka' ); ?> >Sri Lanka</option>
						<option value="Sudan" <?php selected( get_option('fdm-business-country'), 'Sudan' ); ?> >Sudan</option>
						<option value="Suriname" <?php selected( get_option('fdm-business-country'), 'Suriname' ); ?> >Suriname</option>
						<option value="Svalbard and Jan Mayen" <?php selected( get_option('fdm-business-country'), 'Svalbard and Jan Mayen' ); ?> >Svalbard and Jan Mayen</option>
						<option value="Swaziland" <?php selected( get_option('fdm-business-country'), 'Swaziland' ); ?> >Swaziland</option>
						<option value="Sweden" <?php selected( get_option('fdm-business-country'), 'Sweden' ); ?> >Sweden</option>
						<option value="Switzerland" <?php selected( get_option('fdm-business-country'), 'Switzerland' ); ?> >Switzerland</option>
						<option value="Syrian Arab Republic" <?php selected( get_option('fdm-business-country'), 'Syrian Arab Republic' ); ?> >Syrian Arab Republic</option>
						<option value="Taiwan, Province of China" <?php selected( get_option('fdm-business-country'), 'Taiwan, Province of China' ); ?> >Taiwan, Province of China</option>
						<option value="Tajikistan" <?php selected( get_option('fdm-business-country'), 'Tajikistan' ); ?> >Tajikistan</option>
						<option value="Tanzania, United Republic of" <?php selected( get_option('fdm-business-country'), 'Tanzania, United Republic of' ); ?> >Tanzania, United Republic of</option>
						<option value="Thailand" <?php selected( get_option('fdm-business-country'), 'Thailand' ); ?> >Thailand</option>
						<option value="Timor-leste" <?php selected( get_option('fdm-business-country'), 'Timor-leste' ); ?> >Timor-leste</option>
						<option value="Togo" <?php selected( get_option('fdm-business-country'), 'Togo' ); ?> >Togo</option>
						<option value="Tokelau" <?php selected( get_option('fdm-business-country'), 'Tokelau' ); ?> >Tokelau</option>
						<option value="Tonga" <?php selected( get_option('fdm-business-country'), 'Tonga' ); ?> >Tonga</option>
						<option value="Trinidad and Tobago" <?php selected( get_option('fdm-business-country'), 'Trinidad and Tobago' ); ?> >Trinidad and Tobago</option>
						<option value="Tunisia" <?php selected( get_option('fdm-business-country'), 'Tunisia' ); ?> >Tunisia</option>
						<option value="Turkey" <?php selected( get_option('fdm-business-country'), 'Turkey' ); ?> >Turkey</option>
						<option value="Turkmenistan" <?php selected( get_option('fdm-business-country'), 'Turkmenistan' ); ?> >Turkmenistan</option>
						<option value="Turks and Caicos Islands" <?php selected( get_option('fdm-business-country'), 'Turks and Caicos Islands' ); ?> >Turks and Caicos Islands</option>
						<option value="Tuvalu" <?php selected( get_option('fdm-business-country'), 'Tuvalu' ); ?> >Tuvalu</option>
						<option value="Uganda" <?php selected( get_option('fdm-business-country'), 'Uganda' ); ?> >Uganda</option>
						<option value="Ukraine" <?php selected( get_option('fdm-business-country'), 'Ukraine' ); ?> >Ukraine</option>
						<option value="United Arab Emirates" <?php selected( get_option('fdm-business-country'), 'United Arab Emirates' ); ?> >United Arab Emirates</option>
						<option value="United Kingdom" <?php selected( get_option('fdm-business-country'), 'United Kingdom' ); ?> >United Kingdom</option>
						<option value="United States" <?php selected( get_option('fdm-business-country'), 'United States' ); ?> >United States</option>
						<option value="United States Minor Outlying Islands" <?php selected( get_option('fdm-business-country'), 'United States Minor Outlying Islands' ); ?> >United States Minor Outlying Islands</option>
						<option value="Uruguay" <?php selected( get_option('fdm-business-country'), 'Uruguay' ); ?> >Uruguay</option>
						<option value="Uzbekistan" <?php selected( get_option('fdm-business-country'), 'Uzbekistan' ); ?> >Uzbekistan</option>
						<option value="Vanuatu" <?php selected( get_option('fdm-business-country'), 'Vanuatu' ); ?> >Vanuatu</option>
						<option value="Venezuela" <?php selected( get_option('fdm-business-country'), 'Venezuela' ); ?> >Venezuela</option>
						<option value="Viet Nam" <?php selected( get_option('fdm-business-country'), 'Viet Nam' ); ?> >Viet Nam</option>
						<option value="Virgin Islands, British" <?php selected( get_option('fdm-business-country'), 'Virgin Islands, British' ); ?> >Virgin Islands, British</option>
						<option value="Virgin Islands, U.S." <?php selected( get_option('fdm-business-country'), 'Virgin Islands, U.S.' ); ?> >Virgin Islands, U.S.</option>
						<option value="Wallis and Futuna" <?php selected( get_option('fdm-business-country'), 'Wallis and Futuna' ); ?> >Wallis and Futuna</option>
						<option value="Western Sahara" <?php selected( get_option('fdm-business-country'), 'Western Sahara' ); ?> >Western Sahara</option>
						<option value="Yemen" <?php selected( get_option('fdm-business-country'), 'Yemen' ); ?> >Yemen</option>
						<option value="Zambia" <?php selected( get_option('fdm-business-country'), 'Zambia' ); ?> >Zambia</option>
						<option value="Zimbabwe" <?php selected( get_option('fdm-business-country'), 'Zimbabwe' ); ?> >Zimbabwe</option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label><?php esc_html_e( 'Operation mode', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<input type="checkbox" name="myd-operation-mode-delivery" id="myd-operation-mode-delivery" value="delivery" <?php checked( get_option('myd-operation-mode-delivery'), 'delivery' ); ?>>
					<label for="myd-operation-mode-delivery"><?php esc_html_e( 'Delivery', 'myd-delivery-pro' );?></label> <br>

					<input type="checkbox" name="myd-operation-mode-take-away" id="myd-operation-mode-take-away" value="take-away" <?php checked( get_option('myd-operation-mode-take-away'), 'take-away' ); ?>>
					<label for="myd-operation-mode-take-away"><?php esc_html_e( 'Take Away', 'myd-delivery-pro' );?></label> <br>

					<input type="checkbox" name="myd-operation-mode-in-store" id="myd-operation-mode-in-store" value="order-in-store" <?php checked( get_option('myd-operation-mode-in-store'), 'order-in-store' ); ?>>
					<label for="myd-operation-mode-in-store"><?php esc_html_e( 'Digital Menu', 'myd-delivery-pro' );?></label>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="fdm-list-menu-categories"><?php esc_html_e( 'Menu Categories', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<textarea id="fdm-list-menu-categories" name="fdm-list-menu-categories" cols="50" rows="5" class="large-text"><?php echo esc_textarea( get_option('fdm-list-menu-categories') ); ?></textarea>
					<p class="description"><?php esc_html_e( 'List all menu categories separated by comma (,). Like: cat1,cat2,cat3,cat4.', 'myd-delivery-pro' );?></p>
				</td>
			</tr>
		</tbody>
	</table>
</div>
