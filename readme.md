# Description

CodeIgniter library for duplicating data from same table or other table with same structure.

## Purpose

With this library you can easily copy settings or configurations from one user to other.

## Example


```
    /**
     * Import Filters Data from another Partner 
     * 
     * @author Slavi Galabov
     * @param int $partner_id
     * @param int $group_id
     * @return array / bool
     */

    public function import_filters($partner_id = NULL, $group_id = NULL) {

        if(!$partner_id || !$group_id){
		
			$this->errors[] = __('Please provide all required data!');
			
            return FALSE;
        }

        $this->load->library('data_duplicator');

        $donor = array(
            'partner_id' => $this->input->post('selected_partner_id'),
            'group_id' => $group_id
        );

        $recipient = array(
            'partner_id' => $partner_id,
            'group_id' => $group_id
        );

        $data = array();

        if ($this->input->post('load_data')) {
            $data['filters'] = $this->data_duplicator->check($recipient, $donor, 'partner_filters');
        } elseif ($this->input->post('import_filters')) {
            $this->data_duplicator->copy($recipient, $donor, 'partner_filters');
        }
        return $data;
    }
```