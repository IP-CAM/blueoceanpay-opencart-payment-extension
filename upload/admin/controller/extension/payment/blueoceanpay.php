<?php

class ControllerExtensionPaymentBlueoceanpay extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/blueoceanpay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_blueoceanpay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['app_id'])) {
			$data['error_app_id'] = $this->error['app_id'];
		} else {
			$data['error_app_id'] = '';
		}

		if (isset($this->error['app_secret'])) {
			$data['error_app_secret'] = $this->error['app_secret'];
		} else {
			$data['error_app_secret'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/blueoceanpay', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/blueoceanpay', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_blueoceanpay_app_id'])) {
			$data['payment_blueoceanpay_app_id'] = $this->request->post['payment_blueoceanpay_app_id'];
		} else {
			$data['payment_blueoceanpay_app_id'] = $this->config->get('payment_blueoceanpay_app_id');
		}

		if (isset($this->request->post['payment_blueoceanpay_app_secret'])) {
			$data['payment_blueoceanpay_app_secret'] = $this->request->post['payment_blueoceanpay_app_secret'];
		} else {
			$data['payment_blueoceanpay_app_secret'] = $this->config->get('payment_blueoceanpay_app_secret');
		}

		if (isset($this->request->post['payment_blueoceanpay_total'])) {
			$data['payment_blueoceanpay_total'] = $this->request->post['payment_blueoceanpay_total'];
		} else {
			$data['payment_blueoceanpay_total'] = $this->config->get('payment_blueoceanpay_total');
		}

		if (isset($this->request->post['payment_blueoceanpay_currency'])) {
			$data['payment_blueoceanpay_currency'] = $this->request->post['payment_blueoceanpay_currency'];
		} else {
			$data['payment_blueoceanpay_currency'] = $this->config->get('payment_blueoceanpay_currency');
		}

		if (isset($this->request->post['payment_blueoceanpay_completed_status_id'])) {
			$data['payment_blueoceanpay_completed_status_id'] = $this->request->post['payment_blueoceanpay_completed_status_id'];
		} else {
			$data['payment_blueoceanpay_completed_status_id'] = $this->config->get('payment_blueoceanpay_completed_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_blueoceanpay_geo_zone_id'])) {
			$data['payment_blueoceanpay_geo_zone_id'] = $this->request->post['payment_blueoceanpay_geo_zone_id'];
		} else {
			$data['payment_blueoceanpay_geo_zone_id'] = $this->config->get('payment_blueoceanpay_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_blueoceanpay_status'])) {
			$data['payment_blueoceanpay_status'] = $this->request->post['payment_blueoceanpay_status'];
		} else {
			$data['payment_blueoceanpay_status'] = $this->config->get('payment_blueoceanpay_status');
		}

		if (isset($this->request->post['payment_blueoceanpay_sort_order'])) {
			$data['payment_blueoceanpay_sort_order'] = $this->request->post['payment_blueoceanpay_sort_order'];
		} else {
			$data['payment_blueoceanpay_sort_order'] = $this->config->get('payment_blueoceanpay_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/blueocean_pay', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/blueoceanpay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_blueoceanpay_app_id']) {
			$this->error['app_id'] = $this->language->get('error_app_id');
		}

		if (!$this->request->post['payment_blueoceanpay_app_secret']) {
			$this->error['app_secret'] = $this->language->get('error_app_secret');
		}

		return !$this->error;
	}
}
